<?php

declare(strict_types=1);

namespace App\Company\Application\Query\DTO;

class CompanyCollectionDTO
{
    /**
     * @param CompanyDetailsDTO[] $companies
     */
    public function __construct(
        public array $companies,
    ) {
    }
}
