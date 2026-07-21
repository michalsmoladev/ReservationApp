<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
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

    public function employeeHasReservationConflict(
        Uuid $employeeId,
        \DateTimeImmutable $reservationDate,
        float $serviceDuration,
    ): bool {
        $employeeReservations = $this->repository->findBy(['employeeId' => $employeeId]);
        $requestedReservationEnd = $this->calculateReservationEnd($reservationDate, $serviceDuration);

        foreach ($employeeReservations as $employeeReservation) {
            \assert($employeeReservation instanceof Reservation);

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
