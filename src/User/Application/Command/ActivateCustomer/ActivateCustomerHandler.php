<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateCustomer;

use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ActivateCustomerHandler
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ActivateCustomerCommand $command): void
    {
        /** @var Customer $customer */
        $customer = $this->customerRepository->findByToken($command->token);

        $customer->markAsActive();

        $this->customerRepository->save($customer);

        $this->logger->info(
            '[ActivateCustomer] Employee was activated',
            [
                'customer_id' => $customer->getUuid()->toString(),
            ],
        );
    }
}