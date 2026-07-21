<?php

declare(strict_types=1);

namespace App\Company\Application\Query\DTO;

class CompanyAddressCollectionDTO
{
    /**
     * @param CompanyAddressDTO[] $addresses
     */
    public function __construct(
        public array $addresses,
    ) {
    }
}
