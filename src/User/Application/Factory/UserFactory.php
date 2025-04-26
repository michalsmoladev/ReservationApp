<?php

declare(strict_types=1);

namespace App\User\Application\Factory;

use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

class UserFactory
{
    public function create(CreateUserCommand $command): User
    {
        $user = new User(
            email: $command->email,
            password: $command->password,
        );

        $user->setUuid(Uuid::fromString($command->uuid));
        $user->setRoles(['ROLE_USER']);

        return $user;
    }
}