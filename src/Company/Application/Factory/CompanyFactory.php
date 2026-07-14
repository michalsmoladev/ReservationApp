<?php

declare(strict_types=1);

namespace App\Company\Application\Factory;

use App\Company\Application\Command\CreateCompany\DTO\CreateCompanyDTO;
use App\Company\Domain\Entity\Company;
use Symfony\Component\Uid\Uuid;

class CompanyFactory
{
    public function __construct(
        private readonly CompanyAddressFactory $companyAddressFactory,
    ) {
    }

    public function create(Uuid $id, CreateCompanyDTO $companyDTO): Company
    {
        $company = new Company(
            displayName: $companyDTO->displayName,
            legalName: $companyDTO->legalName,
            taxId: $companyDTO->taxId,
            currency: $companyDTO->currency,
        );

        $company->setId($id);

        foreach ($companyDTO->addresses as $address) {
            $company->addAddress($this->companyAddressFactory->create($address));
        }

        return $company;
    }
}
