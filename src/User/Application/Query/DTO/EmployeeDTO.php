<?php

declare(strict_types=1);

namespace App\User\Application\Query\DTO;

class EmployeeDTO
{
    public function __construct(
        public string $email,
        public array $roles,
        public array $jobRoles,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }
}