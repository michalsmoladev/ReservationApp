<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class CreateServiceValidator
{
    public function __construct(
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
    ) {
    }

    public function __invoke(CreateServiceCommand $command): void
    {
        if ('' === trim($command->createServiceDTO->name)) {
            throw new ValidationFail('[CreateService] Service name cannot be blank');
        }

        if ($command->createServiceDTO->duration <= 0) {
            throw new ValidationFail('[CreateService] Service duration must be greater than zero');
        }

        if ($command->createServiceDTO->price < 0) {
            throw new ValidationFail('[CreateService] Service price cannot be negative');
        }

        if (!Uuid::isValid($command->createServiceDTO->companyId)) {
            throw new ValidationFail('[CreateService] Company id must be a valid UUID');
        }

        if (!Uuid::isValid($command->createServiceDTO->companyAddressId)) {
            throw new ValidationFail('[CreateService] Company address id must be a valid UUID');
        }

        $company = $this->companyRepository->findById(Uuid::fromString($command->createServiceDTO->companyId));

        if (!$company || !$company->isActive()) {
            throw new ValidationFail('[CreateService] Company not found');
        }

        $companyAddress = $this->companyAddressRepository->findById(Uuid::fromString($command->createServiceDTO->companyAddressId));

        if (!$companyAddress) {
            throw new ValidationFail('[CreateService] Company address not found');
        }

        if ($companyAddress->getCompany()?->getId()->toString() !== $company->getId()->toString()) {
            throw new ValidationFail('[CreateService] Company address does not belong to the given company');
        }

        $employeeIds = array_values(array_unique($command->createServiceDTO->employeeIds));

        foreach ($employeeIds as $employeeId) {
            if (!\is_string($employeeId) || !Uuid::isValid($employeeId)) {
                throw new ValidationFail('[CreateService] Every employee id must be a valid UUID');
            }
        }

        $employees = $this->employeeRepository->findByIds(array_map(
            static fn (string $employeeId) => Uuid::fromString($employeeId),
            $employeeIds
        ));

        if (\count($employees) !== \count($employeeIds)) {
            throw new ValidationFail('[CreateService] One or more employees were not found');
        }

        foreach ($employees as $employee) {
            if ($employee->getCompany()?->getId()->toString() !== $company->getId()->toString()) {
                throw new ValidationFail('[CreateService] Employee does not belong to the given company');
            }

            if ($employee->getCompanyAddress()?->getId()->toString() !== $companyAddress->getId()->toString()) {
                throw new ValidationFail('[CreateService] Employee does not belong to the given company location');
            }
        }
    }
}
