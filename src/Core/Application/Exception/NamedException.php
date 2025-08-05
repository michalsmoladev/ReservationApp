<?php

declare(strict_types=1);

namespace App\Core\Application\Exception;

abstract class NamedException extends \Exception
{
    public const string API_ERROR_MESSAGE = 'api.apiError';
    public const int API_ERROR_CODE = 2000;

    public const string EMPLOYEE_NOT_FOUND_MESSAGE = 'employee.employeeNotFound';
    public const int EMPLOYEE_NOT_FOUND_CODE = 2001;

    public function getErrorMessage(): string
    {
        return self::API_ERROR_MESSAGE;
    }

    public function getErrorCode(): int
    {
        return self::API_ERROR_CODE;
    }
}