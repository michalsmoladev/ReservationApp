<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateEmployee;

use App\User\Application\Command\CreateEmployee\DTO\CreateEmployeeDto;

class CreateEmployeeCommand
{
    public string $uuid;

    public function __construct(
        public CreateEmployeeDto $employeeDto,
    ) {
    }
}