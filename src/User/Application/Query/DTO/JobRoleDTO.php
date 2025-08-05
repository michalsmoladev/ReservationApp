<?php

declare(strict_types=1);

namespace App\User\Application\Query\DTO;

class JobRoleDTO
{
    public function __construct(
        public string $name,
        public ?string $description,
    ) {
    }
}