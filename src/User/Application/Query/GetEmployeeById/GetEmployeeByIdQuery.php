<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetEmployeeById;

final readonly class GetEmployeeByIdQuery
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}