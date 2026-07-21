<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\DTO;

class ServiceCollectionDTO
{
    /**
     * @param ServiceDetailsDTO[] $services
     */
    public function __construct(
        public array $services,
    ) {
    }
}
