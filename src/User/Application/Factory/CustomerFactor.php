<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\CreateCustomer\DTO\CreateCustomerDTO;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\UserMetadata;
use Symfony\Component\Uid\Uuid;

class CustomerFactor
{
    public function create(CreateCustomerDTO $customerDTO, Uuid $id): Customer
    {
        $metadata = new UserMetadata(
            activationToken: Uuid::v7()->toString(),
            activationExpiresAt: new \DateTimeImmutable('+2 hours'),
        );

        $customer = new Customer(
            email: $customerDTO->email,
            password: $customerDTO->password,
            metadata: $metadata,
            firstname: $customerDTO->firstname,
            lastname: $customerDTO->lastname,
            isActive: false,
            phone: $customerDTO->phone,
        );

        $customer->setUuid($id);
        $customer->setRoles(['ROLE_CUSTOMER']);

        return $customer;
    }
}