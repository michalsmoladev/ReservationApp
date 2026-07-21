<?php

declare(strict_types=1);

namespace App\Company\Application\Command\UpdateCompany\DTO;

class UpdateCompanyDTO
{
    public function __construct(
        public string $displayName,
        public string $legalName,
        public string $taxId,
        public string $currency,
    ) {
    }
}
