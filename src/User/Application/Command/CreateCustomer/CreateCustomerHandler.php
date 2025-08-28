<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateCustomer;

use App\User\Application\Factory\CustomerFactor;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateCustomerHandler
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerFactor $customerFactor,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $customer = $this->customerFactor->create(
            customerDTO: $command->createCustomerDTO,
            id: $command->id,
        );

        $this->customerRepository->save($customer);

        $this->logger->info('[CreateCustomer] Created customer', ['customer_id' => $customer->getUuid()->toString()]);
    }
}