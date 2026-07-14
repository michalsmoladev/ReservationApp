<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Company;
use App\User\Application\Command\CreateEmployee\DTO\CreateEmployeeDto;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\UserMetadata;
use Symfony\Component\Uid\Uuid;

class EmployeeFactory
{
    public function create(CreateEmployeeDto $employeeDto, Uuid $id, Company $company, CompanyAddress $companyAddress): Employee
    {
        $metadata = new UserMetadata(
            activationToken: Uuid::v7()->toString(),
            activationExpiresAt: new \DateTimeImmutable('+2 hours'),
        );

        $employee = new Employee(
            email: $employeeDto->email,
            password: $employeeDto->password,
            metadata: $metadata,
            isActive: false,
            firstname: $employeeDto->firstname,
            lastname: $employeeDto->lastname,
            company: $company,
            companyAddress: $companyAddress,
        );

        $employee->setUuid($id);
        $employee->setRoles(['ROLE_EMPLOYEE']);

        return $employee;
    }
}
