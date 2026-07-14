<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateEmployee;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\User\Application\Factory\EmployeeFactory;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class CreateEmployeeHandler
{
    public function __construct(
        private CompanyAddressRepositoryInterface $companyAddressRepository,
        private CompanyRepositoryInterface $companyRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeFactory $employeeFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateEmployeeCommand $command): void
    {
        $company = $this->companyRepository->findById(Uuid::fromString($command->employeeDto->companyId));
        $companyAddress = $this->companyAddressRepository->findById(Uuid::fromString($command->employeeDto->companyAddressId));

        if (!$company) {
            throw new \RuntimeException('[CreateEmployee] Company not found during employee creation');
        }

        if (!$companyAddress) {
            throw new \RuntimeException('[CreateEmployee] Company address not found during employee creation');
        }

        $employee = $this->employeeFactory->create($command->employeeDto, $command->id, $company, $companyAddress);

        $this->employeeRepository->save($employee);

        $this->logger->info('[CreateEmployee] Created employee', ['employee_id' => $employee->getUuid()->toString()]);
    }
}
