<?php

declare(strict_types=1);

namespace App\User\Application\Exception;

use App\Core\Application\Exception\NamedException;

class EmployeeNotFoundException extends NamedException
{
    public function getErrorCode(): int
    {
        return self::EMPLOYEE_NOT_FOUND_CODE;
    }

    public function getErrorMessage(): string
    {
        return self::EMPLOYEE_NOT_FOUND_MESSAGE;
    }
}