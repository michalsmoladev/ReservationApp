<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateCustomer;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

#[AsMessageValidator]
class ActivateCustomerValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ActivateCustomerCommand $command): void
    {
        $customer = $this->customerRepository->findByToken($command->token);

        if (!$customer) {
            $this->logger->info(
                message: '[ActivateCustomer] Customer not found',
                context: [
                    'token' => $command->token
                ]
            );

            throw new ValidationFail(
                sprintf('[ActivateCustomer] Customer not found with token: %s', $command->token)
            );
        }

        if ($customer->getMetadata()->getActivationExpiresAt() < new \DateTimeImmutable()) {
            throw new ValidationFail('[ActivateCustomer] Token has expired');
        }

        if ($customer->isActive()) {
            throw new ValidationFail('[ActivateCustomer] Customer is already active.');
        }
    }
}
