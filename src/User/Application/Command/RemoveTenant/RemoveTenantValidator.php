<?php

namespace App\User\Application\Command\RemoveTenant;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;

#[AsMessageValidator]
class RemoveTenantValidator
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {
    }

    public function __invoke(RemoveTenantCommand $command): void
    {
        $tenant = $this->tenantRepository->findById($command->id);

        if (!$tenant) {
            throw new ValidationFail('[RemoveTenant] Tenant not found]', [
                'id' => $command->id,
            ]);
        }
    }
}