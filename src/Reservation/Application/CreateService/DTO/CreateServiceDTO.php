<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService\DTO;

class CreateServiceDTO
{
    public function __construct(
        public string $name,
        public float $duration,
        public float $price,
        public ?string $description = null,
    ) {
    }
}
