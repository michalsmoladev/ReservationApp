<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Application\Command\CreateUser\DTO\CreateUserDto;
use App\User\Application\DTO\UserDTO;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserMetadata;
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

        $user = new User(
            email: $userDTO->email,
            password: $userDTO->password,
            metadata: $metadata
        );

        $user->setUuid(Uuid::fromString($uuid));
        $user->setRoles(['ROLE_USER']);

        return $user;
    }
}