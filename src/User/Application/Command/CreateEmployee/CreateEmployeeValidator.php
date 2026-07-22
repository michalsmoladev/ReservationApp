<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateEmployee;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
readonly class CreateEmployeeValidator
{
    public function __construct(
        private CompanyAddressRepositoryInterface $companyAddressRepository,
        private CompanyRepositoryInterface $companyRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateEmployeeCommand $command): void
    {
        $employee = $this->employeeRepository->findByEmail($command->employeeDto->email);

        if ($employee) {
            $this->logger->error('[CreateEmployee] Employee already exists.', ['email' => $command->employeeDto->email]);

            throw new ValidationFail(sprintf('Employee with email %s already exists', $command->employeeDto->email));
        }

        if (!Uuid::isValid($command->employeeDto->companyId)) {
            throw new ValidationFail('[CreateEmployee] Company id must be a valid UUID');
        }

        if (!Uuid::isValid($command->employeeDto->companyAddressId)) {
            throw new ValidationFail('[CreateEmployee] Company address id must be a valid UUID');
        }

        $company = $this->companyRepository->findById(Uuid::fromString($command->employeeDto->companyId));

        if (!$company || !$company->isActive()) {
            throw new ValidationFail('[CreateEmployee] Company not found');
        }

        $companyAddress = $this->companyAddressRepository->findById(Uuid::fromString($command->employeeDto->companyAddressId));

        if (!$companyAddress) {
            throw new ValidationFail('[CreateEmployee] Company address not found');
        }

        if ($companyAddress->getCompany()?->getId()->toString() !== $company->getId()->toString()) {
            throw new ValidationFail('[CreateEmployee] Company address does not belong to the given company');
        }
    }
}
