<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateEmployee;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class UpdateEmployeeHandler
{
    public function __construct(
        private CompanyAddressRepositoryInterface $companyAddressRepository,
        private CompanyRepositoryInterface $companyRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateEmployeeCommand $command): void
    {
        /** @var Employee $employee */
        $employee = $this->employeeRepository->findById($command->id);
        $company = $this->companyRepository->findById(\Symfony\Component\Uid\Uuid::fromString($command->companyId));
        $companyAddress = $this->companyAddressRepository->findById(\Symfony\Component\Uid\Uuid::fromString($command->companyAddressId));

        if (!$company) {
            throw new \RuntimeException('[UpdateEmployee] Company not found during employee update');
        }

        if (!$companyAddress) {
            throw new \RuntimeException('[UpdateEmployee] Company address not found during employee update');
        }

        $employee->update(properties: (array) $command);
        $employee->assignCompany($company);
        $employee->assignCompanyAddress($companyAddress);

        $this->employeeRepository->save($employee);

        $this->logger->info('[UpdateEmployee] Update employee', ['employee_id' => $employee->getUuid()->toString()]);
    }
}
