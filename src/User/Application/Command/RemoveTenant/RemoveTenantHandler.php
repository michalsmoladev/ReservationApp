<?php

namespace App\User\Application\Command\RemoveTenant;

use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveTenantHandler
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {
    }

    public function __invoke(RemoveTenantCommand $command): void
    {
        $tenant = $this->tenantRepository->findById($command->id);

        $this->tenantRepository->remove($tenant);
    }
}