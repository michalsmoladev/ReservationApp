<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\DTO;

class ReservationCollectionDTO
{
    /**
     * @param ReservationDetailsDTO[] $reservations
     */
    public function __construct(
        public array $reservations,
    ) {
    }
}
