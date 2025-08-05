<?php

declare(strict_types=1);

namespace App\User\Application\Command\RemoveEmployee;

class RemoveEmployeeCommand
{
    public function __construct(
        public string $employeeId,
    ) {
    }
}