<?php

namespace App\User\Application\Command\UpdateTenant\DTO;

class UpdateTenantDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public bool $isActive,
        public string $firstname,
        public string $lastname,
    ) {
    }
}