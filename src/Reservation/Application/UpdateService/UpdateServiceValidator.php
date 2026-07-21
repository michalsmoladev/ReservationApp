<?php

declare(strict_types=1);

namespace App\Reservation\Application\UpdateService;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Application\ServiceAccessChecker;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class UpdateServiceValidator
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly ServiceAccessChecker $serviceAccessChecker,
        private readonly Security $security,
    ) {
    }

    public function __invoke(UpdateServiceCommand $command): void
    {
        $service = $this->serviceRepository->findById($command->serviceId);

        if (!$service) {
            throw new ValidationFail('[UpdateService] Service not found');
        }

        if (!$service->isActive()) {
            throw new ValidationFail('[UpdateService] Only active services can be updated');
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[UpdateService] Authenticated user is required');
        }

        if (!$this->serviceAccessChecker->canManageService($user, $service)) {
            throw new ValidationFail('[UpdateService] User cannot manage this service');
        }

        if ('' === trim($command->updateServiceDTO->name)) {
            throw new ValidationFail('[UpdateService] Service name cannot be blank');
        }

        if ($command->updateServiceDTO->duration <= 0) {
            throw new ValidationFail('[UpdateService] Service duration must be greater than zero');
        }

        if ($command->updateServiceDTO->price < 0) {
            throw new ValidationFail('[UpdateService] Service price cannot be negative');
        }

        if (!Uuid::isValid($command->updateServiceDTO->companyAddressId)) {
            throw new ValidationFail('[UpdateService] Company address id must be a valid UUID');
        }

        $companyAddress = $this->companyAddressRepository->findById(Uuid::fromString($command->updateServiceDTO->companyAddressId));

        if (!$companyAddress) {
            throw new ValidationFail('[UpdateService] Company address not found');
        }

        if (!$companyAddress->getCompany()?->getId()->equals($service->getCompany()->getId())) {
            throw new ValidationFail('[UpdateService] Company address does not belong to the service company');
        }

        if (!$this->serviceAccessChecker->canManageCompanyAddress($user, $service->getCompany(), $companyAddress)) {
            throw new ValidationFail('[UpdateService] User cannot move service to this company location');
        }

        $employeeIds = array_values(array_unique($command->updateServiceDTO->employeeIds));

        foreach ($employeeIds as $employeeId) {
            if (!\is_string($employeeId) || !Uuid::isValid($employeeId)) {
                throw new ValidationFail('[UpdateService] Every employee id must be a valid UUID');
            }
        }

        $employees = $this->employeeRepository->findByIds(array_map(
            static fn (string $employeeId) => Uuid::fromString($employeeId),
            $employeeIds,
        ));

        if (\count($employees) !== \count($employeeIds)) {
            throw new ValidationFail('[UpdateService] One or more employees were not found');
        }

        foreach ($employees as $employee) {
            if ($employee->getCompany()?->getId()->toString() !== $service->getCompany()->getId()->toString()) {
                throw new ValidationFail('[UpdateService] Employee does not belong to the service company');
            }

            if ($employee->getCompanyAddress()?->getId()->toString() !== $companyAddress->getId()->toString()) {
                throw new ValidationFail('[UpdateService] Employee does not belong to the selected company location');
            }
        }
    }
}
