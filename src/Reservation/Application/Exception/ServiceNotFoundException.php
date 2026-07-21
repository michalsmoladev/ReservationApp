<?php

declare(strict_types=1);

namespace App\Reservation\Application\Exception;

use App\Core\Application\Exception\NamedException;

class ServiceNotFoundException extends NamedException
{
    public function getErrorCode(): int
    {
        return self::SERVICE_NOT_FOUND_CODE;
    }

    public function getErrorMessage(): string
    {
        return self::SERVICE_NOT_FOUND_MESSAGE;
    }
}
