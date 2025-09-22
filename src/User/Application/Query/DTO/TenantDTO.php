<?php

namespace App\User\Application\Query\DTO;

class TenantDTO
{
    public function __construct(
        public string $email,
        public array $roles,
        public string $firstname,
        public string $lastname,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }
}