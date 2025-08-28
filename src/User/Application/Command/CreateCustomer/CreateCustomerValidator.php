<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateCustomer;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;

#[AsMessageValidator]
class CreateCustomerValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
    }

    public function __invoke(CreateCustomerCommand $command): void
    {
        $customer = $this->customerRepository->findByEmail($command->createCustomerDTO->email);

        if ($customer) {
            throw new ValidationFail('[CreateCustomer] Customer already exists');
        }
    }
}