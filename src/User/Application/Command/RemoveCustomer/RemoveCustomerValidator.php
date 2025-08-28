<?php

declare(strict_types=1);

namespace App\User\Application\Command\RemoveCustomer;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;

#[AsMessageValidator]
class RemoveCustomerValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
    }

    public function __invoke(RemoveCustomerCommand $command): void
    {
        $customer = $this->customerRepository->findById($command->customerId);

        if (!$customer) {
            throw new ValidationFail(
                sprintf(
                    '[RemoveCustomer] Customer not found with id: %s',
                    $command->customerId->toString()
                )
            );
        }
    }
}
