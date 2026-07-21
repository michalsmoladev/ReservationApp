<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateReservation;

use App\Reservation\Application\CreateReservation\DTO\CreateReservationDTO;
use Symfony\Component\Uid\Uuid;

class CreateReservationCommand
{
    public function __construct(
        public CreateReservationDTO $createReservationDTO,
        public Uuid $id,
    ) {
    }
}
