<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Reservation\Application\Factory\ServiceFactory;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateServiceHandler
{
    public function __construct(
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly ServiceFactory $serviceFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateServiceCommand $command): void
    {
        $company = $this->companyRepository->findById(Uuid::fromString($command->createServiceDTO->companyId));
        $companyAddress = $this->companyAddressRepository->findById(Uuid::fromString($command->createServiceDTO->companyAddressId));
        $employees = $this->employeeRepository->findByIds(array_map(
            static fn (string $employeeId) => Uuid::fromString($employeeId),
            array_values(array_unique($command->createServiceDTO->employeeIds))
        ));

        if (!$company) {
            throw new \RuntimeException('[CreateService] Company not found during service creation');
        }

        if (!$companyAddress) {
            throw new \RuntimeException('[CreateService] Company address not found during service creation');
        }

        $service = $this->serviceFactory->create(
            serviceDTO: $command->createServiceDTO,
            id: $command->id,
            company: $company,
            companyAddress: $companyAddress,
            employees: $employees,
        );

        $this->serviceRepository->save($service);

        $this->logger->info('[CreateService] Created service', ['service_id' => $service->getId()->toString()]);
    }
}
