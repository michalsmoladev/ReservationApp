<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateCustomer;

use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateCustomerHandler
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
    }

    public function __invoke(UpdateCustomerCommand $command): void
    {
        /** @var Customer $customer */
        $customer = $this->customerRepository->findById($command->customerId);

        $customer->update((array) $command->dto);
    }
}