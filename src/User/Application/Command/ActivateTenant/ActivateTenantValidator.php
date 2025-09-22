<?php

namespace App\User\Application\Command\ActivateTenant;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

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
                    'token' => $command->token->toString()
                ]
            );

            throw new ValidationFail('[ActivateTenant] Tenant not found', [
                'token' => $command->token->toString()
            ]);
        }
    }
}