<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation\ReservationStatusEnum;
use App\Reservation\Domain\Entity\Service;
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

    public function findByGuestCancellationToken(string $guestCancellationToken): ?Reservation
    {
        $reservation = $this->repository->findOneBy(['guestCancellationToken' => $guestCancellationToken]);

        \assert($reservation instanceof Reservation || null === $reservation);

        return $reservation;
    }

    public function findByFilters(
        ?Uuid $companyId,
        ?Uuid $companyAddressId,
        ?Uuid $employeeId,
        ?Uuid $customerId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        ?string $status,
        ?array $companyIds = null,
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('r')
            ->from(Reservation::class, 'r')
            ->join(Service::class, 's', 'WITH', 'r.serviceId = s.id')
            ->orderBy('r.reservationDate', 'ASC')
        ;

        if (null !== $companyId) {
            $qb->andWhere('IDENTITY(s.company) = :companyId')
                ->setParameter('companyId', $companyId);
        }

        if (null !== $companyAddressId) {
            $qb->andWhere('IDENTITY(s.companyAddress) = :companyAddressId')
                ->setParameter('companyAddressId', $companyAddressId);
        }

        if (null !== $employeeId) {
            $qb->andWhere('r.employeeId = :employeeId')
                ->setParameter('employeeId', $employeeId);
        }

        if (null !== $customerId) {
            $qb->andWhere('r.customerId = :customerId')
                ->setParameter('customerId', $customerId);
        }

        if (null !== $from) {
            $qb->andWhere('r.reservationDate >= :from')
                ->setParameter('from', $from);
        }

        if (null !== $to) {
            $qb->andWhere('r.reservationDate <= :to')
                ->setParameter('to', $to);
        }

        if (null !== $status) {
            $qb->andWhere('r.status = :status')
                ->setParameter('status', $status);
        }

        if (null !== $companyIds) {
            if ([] === $companyIds) {
                return [];
            }

            $qb->andWhere('IDENTITY(s.company) IN (:companyIds)')
                ->setParameter('companyIds', $companyIds);
        }

        return $qb->getQuery()->getResult();
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

    public function claimGuestReservationsByEmail(Uuid $customerId, string $email): int
    {
        return $this->entityManager->createQueryBuilder()
            ->update(Reservation::class, 'r')
            ->set('r.customerId', ':customerId')
            ->where('r.customerId IS NULL')
            ->andWhere('LOWER(r.guestEmail) = :email')
            ->setParameter('customerId', $customerId)
            ->setParameter('email', mb_strtolower(trim($email)))
            ->getQuery()
            ->execute()
        ;
    }

    public function save(Reservation $reservation): void
    {
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();
    }

    public function existsActiveByCompanyId(Uuid $companyId): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Reservation::class, 'r')
            ->join(Service::class, 's', 'WITH', 'r.serviceId = s.id')
            ->where('IDENTITY(s.company) = :companyId')
            ->andWhere('r.status != :canceledStatus')
            ->setParameter('companyId', $companyId)
            ->setParameter('canceledStatus', ReservationStatusEnum::CANCELED->value)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    private function calculateReservationEnd(
        \DateTimeImmutable $reservationDate,
        float $serviceDuration,
    ): \DateTimeImmutable {
        $durationInSeconds = (int) round($serviceDuration * 60);

        return $reservationDate->modify(sprintf('+%d seconds', $durationInSeconds));
    }
}
