<?php

declare(strict_types=1);

namespace App\Reservation\Application\DeactivateService;

use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeactivateServiceHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeactivateServiceCommand $command): void
    {
        $service = $this->serviceRepository->findById($command->serviceId);

        if (!$service) {
            throw new \RuntimeException('[DeactivateService] Service not found during deactivation');
        }

        $service->deactivate();
        $this->serviceRepository->save($service);

        $this->logger->info('[DeactivateService] Deactivated service', ['service_id' => $service->getId()->toString()]);
    }
}
