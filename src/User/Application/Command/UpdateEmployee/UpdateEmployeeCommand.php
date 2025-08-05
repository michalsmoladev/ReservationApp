<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateEmployee;

class UpdateEmployeeCommand
{
    public function __construct(
        public string $uuid,
        public string $email,
        public string $password,
        public array $roles,
        public bool $isActive,
    ) {
    }
}