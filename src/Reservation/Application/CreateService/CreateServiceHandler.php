<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

use App\Reservation\Application\Factory\ServiceFactory;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateServiceHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly ServiceFactory $serviceFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateServiceCommand $command): void
    {
        $service = $this->serviceFactory->create(
            serviceDTO: $command->createServiceDTO,
            id: $command->id,
        );

        $this->serviceRepository->save($service);

        $this->logger->info('[CreateService] Created service', ['service_id' => $service->getId()->toString()]);
    }
}
