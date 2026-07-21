<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\DTO;

class ServiceDetailsDTO
{
    /**
     * @param string[] $employeeIds
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public float $duration,
        public float $price,
        public string $companyId,
        public string $companyAddressId,
        public array $employeeIds,
        public bool $isActive,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }
}
