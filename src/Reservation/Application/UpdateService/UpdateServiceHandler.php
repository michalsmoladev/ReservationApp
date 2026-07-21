<?php

declare(strict_types=1);

namespace App\Reservation\Application\UpdateService;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class UpdateServiceHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateServiceCommand $command): void
    {
        $service = $this->serviceRepository->findById($command->serviceId);
        $companyAddress = $this->companyAddressRepository->findById(Uuid::fromString($command->updateServiceDTO->companyAddressId));

        if (!$service) {
            throw new \RuntimeException('[UpdateService] Service not found during update');
        }

        if (!$companyAddress) {
            throw new \RuntimeException('[UpdateService] Company address not found during update');
        }

        $employees = $this->employeeRepository->findByIds(array_map(
            static fn (string $employeeId) => Uuid::fromString($employeeId),
            array_values(array_unique($command->updateServiceDTO->employeeIds)),
        ));

        $service->update([
            'name' => $command->updateServiceDTO->name,
            'description' => $command->updateServiceDTO->description,
            'duration' => $command->updateServiceDTO->duration,
            'price' => $command->updateServiceDTO->price,
        ]);
        $service->assignCompanyAddress($companyAddress);
        $service->syncEmployees($employees);

        $this->serviceRepository->save($service);

        $this->logger->info('[UpdateService] Updated service', ['service_id' => $service->getId()->toString()]);
    }
}
