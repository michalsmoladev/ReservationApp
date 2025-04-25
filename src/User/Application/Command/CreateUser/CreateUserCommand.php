<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUser;

class CreateUserCommand
{
    public string $uuid;

    public function __construct(
        public string $email,
        public string $password
    ) {
    }
}