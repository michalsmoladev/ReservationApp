<?php

declare(strict_types=1);

namespace App\Company\Application\Command\CreateCompany\DTO;

class CreateCompanyAddressDTO
{
    public function __construct(
        public string $street,
        public string $city,
        public int $apartmentNo,
        public int $buildingNo,
        public string $postCode,
        public string $country,
        public ?string $name,
    ) {
    }
}
