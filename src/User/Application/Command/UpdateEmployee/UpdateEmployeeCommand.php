<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateEmployee;

use Symfony\Component\Uid\Uuid;

class UpdateEmployeeCommand
{
    public function __construct(
        public Uuid $id,
        public string $email,
        public string $password,
        public array $roles,
        public bool $isActive,
    ) {
    }
}