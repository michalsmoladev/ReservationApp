<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateGuestReservation\DTO;

class CreateGuestReservationDTO
{
    public function __construct(
        public string $serviceId,
        public string $reservationDate,
        public string $firstname,
        public string $lastname,
        public string $email,
        public string $phone,
        public ?string $employeeId = null,
        public ?string $note = null,
    ) {
    }
}
