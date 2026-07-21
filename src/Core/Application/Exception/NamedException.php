<?php

declare(strict_types=1);

namespace App\Core\Application\Exception;

abstract class NamedException extends \Exception
{
    public const string API_ERROR_MESSAGE = 'api.apiError';
    public const int API_ERROR_CODE = 2000;

    public const string EMPLOYEE_NOT_FOUND_MESSAGE = 'employee.employeeNotFound';
    public const int EMPLOYEE_NOT_FOUND_CODE = 2001;

    public const string TENANT_NOT_FOUND_MESSAGE = 'tenant.tenantNotFound';
    public const int TENANT_NOT_FOUND_CODE = 2002;

    public const string CUSTOMER_NOT_FOUND_MESSAGE = 'customer.customerNotFound';
    public const int CUSTOMER_NOT_FOUND_CODE = 2003;

    public const string SERVICE_NOT_FOUND_MESSAGE = 'service.serviceNotFound';
    public const int SERVICE_NOT_FOUND_CODE = 2004;

    public const string RESERVATION_NOT_FOUND_MESSAGE = 'reservation.reservationNotFound';
    public const int RESERVATION_NOT_FOUND_CODE = 2005;

    public function getErrorMessage(): string
    {
        return self::API_ERROR_MESSAGE;
    }

    public function getErrorCode(): int
    {
        return self::API_ERROR_CODE;
    }
}
