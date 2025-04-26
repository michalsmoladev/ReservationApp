<?php

declare(strict_types=1);

namespace App\User\Application\Command\RemoveUser;

readonly class RemoveUserCommand
{
    public function __construct(
        public string $uuid,
    ) {
    }
}