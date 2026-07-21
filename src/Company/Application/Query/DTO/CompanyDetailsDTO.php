<?php

declare(strict_types=1);

namespace App\Company\Application\Query\DTO;

class CompanyDetailsDTO
{
    /**
     * @param CompanyAddressDTO[] $addresses
     */
    public function __construct(
        public string $id,
        public string $displayName,
        public string $legalName,
        public string $taxId,
        public string $currency,
        public array $addresses,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }
}
