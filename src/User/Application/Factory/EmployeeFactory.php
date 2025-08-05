<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\CreateEmployee\DTO\CreateEmployeeDto;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\UserMetadata;
use Symfony\Component\Uid\Uuid;

class EmployeeFactory
{
    public function create(CreateEmployeeDto $employeeDto, string $uuid): Employee
    {
        $metadata = new UserMetadata(
            activationToken: Uuid::v7()->toString(),
            activationExpiresAt: new \DateTimeImmutable('+2 hours'),
        );

        $employee = new Employee(
            email: $employeeDto->email,
            password: $employeeDto->password,
            metadata: $metadata,
        );

        $employee->setUuid(Uuid::fromString($uuid));
        $employee->setRoles(['ROLE_EMPLOYEE']);

        return $employee;
    }
}