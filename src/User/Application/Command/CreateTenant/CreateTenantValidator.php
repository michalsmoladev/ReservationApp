<?php

namespace App\User\Application\Command\CreateTenant;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;

#[AsMessageValidator]
class CreateTenantValidator
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {
    }

    public function __invoke(CreateTenantCommand $command): void
    {
        $tenant = $this->tenantRepository->findByEmail($command->tenantDTO->email);

        if ($tenant) {
            throw new ValidationFail("User exists");
        }
    }
}