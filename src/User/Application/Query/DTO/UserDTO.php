<?php

declare(strict_types=1);

namespace App\User\Application\Query\DTO;

final readonly class UserDTO
{
    public function __construct(
        public string $email,
        public array $roles,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }
}