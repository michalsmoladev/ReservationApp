<?php

declare(strict_types=1);

namespace App\Reservation\Application\AcceptReservation;

use Symfony\Component\Uid\Uuid;

class AcceptReservationCommand
{
    public function __construct(
        public readonly Uuid $reservationId,
    ) {
    }
}
