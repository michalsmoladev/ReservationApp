<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

class CreateServiceCommand
{
    public function __construct(
        public string $name,
        public string $description,
        public float $duration,
        public float $price,
    ) {
    }
}