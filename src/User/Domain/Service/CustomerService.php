<?php

declare(strict_types=1);

namespace App\User\Domain\Service;

use App\User\Application\Query\DTO\CustomerDTO;
use App\User\Domain\Entity\Customer\Customer;

class CustomerService
{
    public function createCustomerDtoFromCustomer(Customer $customer): CustomerDTO
    {
        return new CustomerDTO(
            email: $customer->getEmail(),
            roles: $customer->getRoles(),
            firstname: $customer->getFirstName(),
            lastname: $customer->getLastName(),
            phone: $customer->getPhone(),
            createdAt: $customer->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            updatedAt: $customer->getUpdatedAt()?->format(\DateTimeImmutable::ATOM),
        );
    }
}