<?php

declare(strict_types=1);

namespace App\Company\Domain\Service;

use App\Company\Application\Query\DTO\CompanyAddressDTO;
use App\Company\Application\Query\DTO\CompanyDetailsDTO;
use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Company;

class CompanyService
{
    public function createDtoFromEntity(Company $company): CompanyDetailsDTO
    {
        $addresses = array_map(
            fn (CompanyAddress $address): CompanyAddressDTO => new CompanyAddressDTO(
                id: $address->getId()->toString(),
                street: $address->getStreet(),
                city: $address->getCity(),
                country: $address->getCountry(),
                postalCode: $address->getPostalCode(),
                apartmentNo: $address->getApartmentNo(),
                buildingNo: $address->getBuildingNo(),
                name: $address->getName(),
                createdAt: $address->getCreatedAt()->format(\DateTimeImmutable::ATOM),
                updatedAt: $address->getUpdatedAt()?->format(\DateTimeImmutable::ATOM),
            ),
            $company->getAddresses()->toArray(),
        );

        return new CompanyDetailsDTO(
            id: $company->getId()->toString(),
            displayName: $company->getDisplayName(),
            legalName: $company->getLegalName(),
            taxId: $company->getTaxId(),
            currency: $company->getCurrency(),
            addresses: $addresses,
            createdAt: $company->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            updatedAt: $company->getUpdatedAt()?->format(\DateTimeImmutable::ATOM),
        );
    }
}
