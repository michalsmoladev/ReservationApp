<?php

declare(strict_types=1);

namespace App\Reservation\Application\UpdateService\DTO;

class UpdateServiceDTO
{
    /**
     * @param string[] $employeeIds
     */
    public function __construct(
        public string $name,
        public ?string $description,
        public float $duration,
        public float $price,
        public string $companyAddressId,
        public array $employeeIds = [],
    ) {
    }
}
