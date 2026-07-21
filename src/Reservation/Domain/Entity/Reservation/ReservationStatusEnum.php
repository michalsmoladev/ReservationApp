<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity\Reservation;

enum ReservationStatusEnum: string
{
    case WAITING_FOR_APPROVAL = 'waiting_for_approval';
}
