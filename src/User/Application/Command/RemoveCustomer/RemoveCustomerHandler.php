<?php

declare(strict_types=1);

namespace App\User\Application\Command\RemoveCustomer;

use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RemoveCustomerHandler
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(RemoveCustomerCommand $command): void
    {
        $customer = $this->customerRepository->findById($command->customerId);

        $this->customerRepository->remove($customer);

        $this->logger->info(
            '[RemoveCustomer] Customer has been removed',
            [
                'customer_id' => $command->customerId->toString()
            ]
        );
    }
}