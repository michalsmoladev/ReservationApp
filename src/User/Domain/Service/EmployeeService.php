<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

use App\User\Application\Query\DTO\EmployeeDTO;
use App\User\Application\Query\DTO\JobRoleDTO;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\JobRole;
use Doctrine\Common\Collections\Collection;

class EmployeeService
{
    public function __construct(
        private readonly JobRoleService $jobRoleService,
    ) {
    }

    public function createEmployeeDtoFromEmployee(Employee $employee): EmployeeDto
    {
        return new EmployeeDTO(
            email: $employee->getEmail(),
            roles: $employee->getRoles(),
            jobRoles: array_map(
                fn (JobRole $jobRole) => $this->jobRoleService->createDtoFromEntity($jobRole),
                $employee->getJobRoles()->toArray()
            ),
            createdAt: $employee->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            updatedAt: $employee->getUpdatedAt()?->format(\DateTimeImmutable::ATOM)
        );
    }
}