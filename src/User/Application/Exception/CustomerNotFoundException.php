<?php

namespace App\User\Application\Exception;

use App\Core\Application\Exception\NamedException;

class CustomerNotFoundException extends NamedException
{
    public function getErrorMessage(): string
    {
        return self::CUSTOMER_NOT_FOUND_MESSAGE;
    }

    public function getErrorCode(): int
    {
        return self::CUSTOMER_NOT_FOUND_CODE;
    }

}