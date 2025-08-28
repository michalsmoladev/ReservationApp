<?php

declare(strict_types=1);

namespace App\User\Application\Query\DTO;

class CustomerDTO
{
    public function __construct(
        public string $email,
        public array $roles,
        public string $firstname,
        public string $lastname,
        public string $phone,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }
}