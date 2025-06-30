<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateUser;

class ActivateUserCommand
{
    public function __construct(
        public readonly string $token,
    ) {
    }
}