<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateEmployee;

use App\User\Application\Command\CreateEmployee\DTO\CreateEmployeeDto;
use Symfony\Component\Uid\Uuid;

class CreateEmployeeCommand
{
    public Uuid $id;

    public function __construct(
        public CreateEmployeeDto $employeeDto,
    ) {
    }
}