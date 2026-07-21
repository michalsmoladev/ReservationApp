<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity\Reservation;

use App\Reservation\Domain\Entity\Reservation;

interface ReservationRepositoryInterface
{
    public function save(Reservation $reservation): void;
}
