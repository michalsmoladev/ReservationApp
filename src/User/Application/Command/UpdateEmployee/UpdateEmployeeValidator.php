<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateEmployee;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
readonly class UpdateEmployeeValidator
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
        $employee = $this->employeeRepository->findById($command->id);

        if (!$employee) {
            $this->logger->error('[UpdateEmployee] Employee does not exists.', ['uuid' => $command->id]);

            throw new ValidationFail('Employee does not exists');
        }

        if (!Uuid::isValid($command->companyId)) {
            throw new ValidationFail('[UpdateEmployee] Company id must be a valid UUID');
        }

        if (!Uuid::isValid($command->companyAddressId)) {
            throw new ValidationFail('[UpdateEmployee] Company address id must be a valid UUID');
        }

        $company = $this->companyRepository->findById(Uuid::fromString($command->companyId));

        if (!$company || !$company->isActive()) {
            throw new ValidationFail('[UpdateEmployee] Company not found');
        }

        $companyAddress = $this->companyAddressRepository->findById(Uuid::fromString($command->companyAddressId));

        if (!$companyAddress) {
            throw new ValidationFail('[UpdateEmployee] Company address not found');
        }

        if ($companyAddress->getCompany()?->getId()->toString() !== $company->getId()->toString()) {
            throw new ValidationFail('[UpdateEmployee] Company address does not belong to the given company');
        }
    }
}
