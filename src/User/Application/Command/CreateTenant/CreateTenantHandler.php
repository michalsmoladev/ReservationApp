<?php

namespace App\User\Application\Command\CreateTenant;

use App\User\Application\Factory\TenantFactory;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateTenantHandler
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly TenantFactory $tenantFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateTenantCommand $command): void
    {
        $tenant = $this->tenantFactory->create(
            id: $command->id,
            tenantDTO: $command->tenantDTO,
        );

        $this->tenantRepository->save($tenant);

        $this->logger->info('[CreateTenant] Tenant created', [
            'id' => $command->id->toString(),
        ]);
    }
}