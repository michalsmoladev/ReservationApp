<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateGuestReservation;

use App\Reservation\Application\CreateGuestReservation\DTO\CreateGuestReservationDTO;
use Symfony\Component\Uid\Uuid;

class CreateGuestReservationCommand
{
    public function __construct(
        public CreateGuestReservationDTO $createGuestReservationDTO,
        public Uuid $id,
    ) {
    }
}
