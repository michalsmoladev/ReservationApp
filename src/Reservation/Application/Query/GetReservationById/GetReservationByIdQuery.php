<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetReservationById;

use Symfony\Component\Uid\Uuid;

final readonly class GetReservationByIdQuery
{
    public function __construct(
        public Uuid $reservationId,
    ) {
    }
}
