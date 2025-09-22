<?php

namespace App\User\Application\Command\UpdateTenant;

use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateTenantHandler
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateTenantCommand $command): void
    {
        $tenant = $this->tenantRepository->findById($command->id);

        $tenant->update((array) $command->tenantDTO);

        $this->tenantRepository->save($tenant);

        $this->logger->info('[UpdateTenant] Tenant updated successfully', [
            'id' => $command->id,
            'tenant' => $tenant,
        ]);
    }
}