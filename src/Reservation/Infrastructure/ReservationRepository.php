<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation\ReservationStatusEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class ReservationRepository implements ReservationRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(Reservation::class);
    }

    public function findById(Uuid $id): ?Reservation
    {
        $reservation = $this->repository->find($id);

        \assert($reservation instanceof Reservation || null === $reservation);

        return $reservation;
    }

    public function findActiveByEmployeesAndDateRange(
        array $employeeIds,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        if ([] === $employeeIds) {
            return [];
        }

        $reservations = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Reservation::class, 'r')
            ->where('r.employeeId IN (:employeeIds)')
            ->andWhere('r.status != :canceledStatus')
            ->andWhere('r.reservationDate < :to')
            ->setParameter('employeeIds', $employeeIds)
            ->setParameter('canceledStatus', ReservationStatusEnum::CANCELED->value)
            ->setParameter('to', $to)
            ->orderBy('r.reservationDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return array_values(array_filter(
            $reservations,
            function (Reservation $reservation) use ($from): bool {
                $reservationEnd = $reservation->getReservationDate()
                    ->modify(sprintf('+%d seconds', (int) round($reservation->getServiceDuration() * 60)));

                return $reservationEnd > $from;
            },
        ));
    }

    public function employeeHasReservationConflict(
        Uuid $employeeId,
        \DateTimeImmutable $reservationDate,
        float $serviceDuration,
    ): bool {
        $employeeReservations = $this->repository->findBy(['employeeId' => $employeeId]);
        $requestedReservationEnd = $this->calculateReservationEnd($reservationDate, $serviceDuration);

        foreach ($employeeReservations as $employeeReservation) {
            \assert($employeeReservation instanceof Reservation);

            if ($employeeReservation->getStatus() === ReservationStatusEnum::CANCELED->value) {
                continue;
            }

            $existingReservationStart = $employeeReservation->getReservationDate();
            $existingReservationEnd = $this->calculateReservationEnd(
                $existingReservationStart,
                $employeeReservation->getServiceDuration(),
            );

            if ($existingReservationStart < $requestedReservationEnd && $existingReservationEnd > $reservationDate) {
                return true;
            }
        }

        return false;
    }

    public function save(Reservation $reservation): void
    {
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();
    }

    private function calculateReservationEnd(
        \DateTimeImmutable $reservationDate,
        float $serviceDuration,
    ): \DateTimeImmutable {
        $durationInSeconds = (int) round($serviceDuration * 60);

        return $reservationDate->modify(sprintf('+%d seconds', $durationInSeconds));
    }
}
