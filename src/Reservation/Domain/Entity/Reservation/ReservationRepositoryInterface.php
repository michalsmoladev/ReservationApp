<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity\Reservation;

use App\Reservation\Domain\Entity\Reservation;
use Symfony\Component\Uid\Uuid;

interface ReservationRepositoryInterface
{
    public function findById(Uuid $id): ?Reservation;

    public function findByGuestCancellationToken(string $guestCancellationToken): ?Reservation;

    /**
     * @param Uuid[]|null $companyIds
     * @return Reservation[]
     */
    public function findByFilters(
        ?Uuid $companyId,
        ?Uuid $companyAddressId,
        ?Uuid $employeeId,
        ?Uuid $customerId,
        ?\DateTimeImmutable $from,
        ?\DateTimeImmutable $to,
        ?string $status,
        ?array $companyIds = null,
    ): array;

    /**
     * @param Uuid[] $employeeIds
     * @return Reservation[]
     */
    public function findActiveByEmployeesAndDateRange(
        array $employeeIds,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;

    public function employeeHasReservationConflict(
        Uuid $employeeId,
        \DateTimeImmutable $reservationDate,
        float $serviceDuration,
    ): bool;

    public function claimGuestReservationsByEmail(Uuid $customerId, string $email): int;

    public function save(Reservation $reservation): void;
}
