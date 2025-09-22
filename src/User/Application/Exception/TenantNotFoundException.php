<?php

namespace App\User\Application\Exception;

use App\Core\Application\Exception\NamedException;

class TenantNotFoundException extends NamedException
{
    public function getErrorCode(): int
    {
        return self::TENANT_NOT_FOUND_CODE;
    }

    public function getErrorMessage(): string
    {
        return self::TENANT_NOT_FOUND_MESSAGE;
    }
}