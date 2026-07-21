<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateReservation\DTO;

class CreateReservationDTO
{
    public function __construct(
        public string $serviceId,
        public string $customerId,
        public string $reservationDate,
        public ?string $employeeId = null,
        public ?string $note = null,
    ) {
    }
}
