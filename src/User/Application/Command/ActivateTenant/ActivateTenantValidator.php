<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateTenant;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use Psr\Log\LoggerInterface;

#[AsMessageValidator]
class ActivateTenantValidator
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ActivateTenantCommand $command): void
    {
        $tenant = $this->tenantRepository->findByToken(token: $command->token);

        if (!$tenant) {
            $this->logger->info(
                message: '[ActivateTenant] Tenant not found',
                context: [
                    'token' => $command->token
                ]
            );

            throw new ValidationFail(
                sprintf('[ActivateTenant] Tenant not found with token: %s', $command->token)
            );
        }

        if ($tenant->getMetadata()->getActivationExpiresAt() < new \DateTimeImmutable()) {
            throw new ValidationFail('[ActivateTenant] Token has expired');
        }

        if ($tenant->isActive()) {
            throw new ValidationFail('[ActivateTenant] Tenant is already active.');
        }
    }
}
