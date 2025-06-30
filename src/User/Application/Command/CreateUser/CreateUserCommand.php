<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUser;

use App\User\Application\Command\CreateUser\DTO\CreateUserDto;

class CreateUserCommand
{
    public string $uuid;

    public function __construct(
        public CreateUserDto $userDTO,
    ) {
    }
}