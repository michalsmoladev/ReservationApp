<?php

declare(strict_types=1);

namespace App\Company\Application\Factory;

use App\Company\Application\Command\CreateCompany\DTO\CreateCompanyAddressDTO;
use App\Company\Domain\Entity\Address\CompanyAddress;

class CompanyAddressFactory
{
    public function create(CreateCompanyAddressDTO $companyAddressDTO): CompanyAddress
    {
        return new CompanyAddress(
            street: $companyAddressDTO->street,
            city: $companyAddressDTO->city,
            country: $companyAddressDTO->country,
            postalCode: $companyAddressDTO->postCode,
            apartmentNo: $companyAddressDTO->apartmentNo,
            buildingNo: $companyAddressDTO->buildingNo,
            name: $companyAddressDTO->name,
        );
    }
}
