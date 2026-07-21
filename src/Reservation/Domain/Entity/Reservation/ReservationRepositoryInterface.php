<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity\Reservation;

use App\Reservation\Domain\Entity\Reservation;
use Symfony\Component\Uid\Uuid;

interface ReservationRepositoryInterface
{
    public function employeeHasReservationConflict(
        Uuid $employeeId,
        \DateTimeImmutable $reservationDate,
        float $serviceDuration,
    ): bool;

    public function save(Reservation $reservation): void;
}
