<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateCustomer;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;

#[AsMessageValidator]
class ActivateCustomerValidator
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
    }

    public function __invoke(): void
    {
        
    }
}