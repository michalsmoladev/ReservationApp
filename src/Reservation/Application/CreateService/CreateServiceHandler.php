<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Reservation\Application\Factory\ServiceFactory;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateServiceHandler
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly ServiceFactory $serviceFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateServiceCommand $command): void
    {
        $company = $this->companyRepository->findById(Uuid::fromString($command->createServiceDTO->companyId));

        if (!$company) {
            throw new \RuntimeException('[CreateService] Company not found during service creation');
        }

        $service = $this->serviceFactory->create(
            serviceDTO: $command->createServiceDTO,
            id: $command->id,
            company: $company,
        );

        $this->serviceRepository->save($service);

        $this->logger->info('[CreateService] Created service', ['service_id' => $service->getId()->toString()]);
    }
}
