<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\CreateUser\DTO\CreateUserDto;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserMetadata;
use App\User\Domain\Entity\UserType;
use App\User\Domain\Exception\UnknownUserTypeException;
use Symfony\Component\Uid\Uuid;

class UserFactory
{
    public function create(CreateUserDto $userDTO, string $uuid): User
    {
        $metadata = new UserMetadata(
            activationToken:(string) Uuid::v7(),
            activationExpiresAt: new \DateTimeImmutable('+2 hours')
        );
        $metadata->setId(Uuid::v7());

        $user = match ($userDTO->type) {
            UserType::EMPLOYEE->value => $this->createEmployee($userDTO, $metadata),
            UserType::CUSTOMER->value => $this->createCustomer($userDTO, $metadata),
            default => throw new UnknownUserTypeException(),
        };

        $user->setUuid(Uuid::fromString($uuid));
        $user->setRoles(['ROLE_USER']);

        return $user;
    }

    private function createEmployee(CreateUserDto $userDTO, UserMetadata $metadata): Employee
    {
        return new Employee(
            email: $userDTO->email,
            password: $userDTO->password,
            metadata: $metadata,
        );
    }

    private function createCustomer(CreateUserDto $userDTO, UserMetadata $metadata): Customer
    {
        return new Customer(
            email: $userDTO->email,
            password: $userDTO->password,
            metadata: $metadata,
        );
    }
}