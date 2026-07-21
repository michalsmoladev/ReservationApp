<?php

declare(strict_types=1);

namespace App\Company\Application\Query\DTO;

class CompanyAddressDTO
{
    public function __construct(
        public string $id,
        public string $street,
        public string $city,
        public string $country,
        public string $postalCode,
        public int $apartmentNo,
        public int $buildingNo,
        public ?string $name,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }
}
