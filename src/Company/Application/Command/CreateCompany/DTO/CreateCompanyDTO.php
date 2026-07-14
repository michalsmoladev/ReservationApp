<?php

declare(strict_types=1);

namespace App\Company\Application\Command\CreateCompany\DTO;

class CreateCompanyDTO
{
    /**
     * @param CreateCompanyAddressDTO[] $addresses
     */
    public function __construct(
        public string $displayName,
        public string $legalName,
        public string $taxId,
        public string $currency,
        public array $addresses,
    ) {
    }
}
