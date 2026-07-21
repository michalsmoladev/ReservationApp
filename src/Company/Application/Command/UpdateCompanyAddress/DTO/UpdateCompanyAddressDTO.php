<?php

declare(strict_types=1);

namespace App\Company\Application\Command\UpdateCompanyAddress\DTO;

class UpdateCompanyAddressDTO
{
    public function __construct(
        public string $street,
        public string $city,
        public int $apartmentNo,
        public int $buildingNo,
        public string $postalCode,
        public string $country,
        public ?string $name,
    ) {
    }
}
