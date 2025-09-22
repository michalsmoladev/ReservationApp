<?php

namespace App\User\Application\Command\ActivateTenant;

use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ActivateTenantHandler
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ActivateTenantCommand $command): void
    {
        $tenant = $this->tenantRepository->findByToken($command->token);

        $tenant->markAsActive();

        $this->tenantRepository->save($tenant);

        $this->logger->info(
            '[ActivateTenant] Tenant was activated',
            [
                'tenant_id' => $tenant->getUuid()->toString(),
            ],
        );
    }
}