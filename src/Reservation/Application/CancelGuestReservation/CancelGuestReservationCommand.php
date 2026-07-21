<?php

declare(strict_types=1);

namespace App\Reservation\Application\CancelGuestReservation;

class CancelGuestReservationCommand
{
    public function __construct(
        public readonly string $guestCancellationToken,
    ) {
    }
}
