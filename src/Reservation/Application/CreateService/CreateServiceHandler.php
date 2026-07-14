<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

use App\Reservation\Application\Factory\ServiceFactory;
use App\Reservation\Domain\Entity\ServiceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateServiceHandler
{
    public function __construct(
        private ServiceRepositoryInterface $serviceRepository,
        private ServiceFactory $serviceFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateServiceCommand $command): void
    {
        $service = $this->serviceFactory->create($command->id, $command->serviceDTO);

        $this->serviceRepository->save($service);

        $this->logger->info('[CreateService] Service created', [
            'service_id' => $service->getId()->toString(),
        ]);
    }
}
