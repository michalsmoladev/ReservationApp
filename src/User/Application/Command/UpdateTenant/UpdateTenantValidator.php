<?php

namespace App\User\Application\Command\UpdateTenant;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;

#[AsMessageValidator]
class UpdateTenantValidator
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {
    }

    public function __invoke(UpdateTenantCommand $command): void
    {
        $tenant = $this->tenantRepository->findById($command->id);

        if (!$tenant) {
            throw new ValidationFail('[Update Tenant] Tenant not found', [
                'id' => $command->id,
            ]);
        }
    }
}