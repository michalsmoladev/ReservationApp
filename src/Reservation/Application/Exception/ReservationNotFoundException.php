<?php

declare(strict_types=1);

namespace App\Reservation\Application\Exception;

use App\Core\Application\Exception\NamedException;

class ReservationNotFoundException extends NamedException
{
    public function getErrorCode(): int
    {
        return self::RESERVATION_NOT_FOUND_CODE;
    }

    public function getErrorMessage(): string
    {
        return self::RESERVATION_NOT_FOUND_MESSAGE;
    }
}
