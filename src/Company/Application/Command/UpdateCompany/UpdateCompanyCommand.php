<?php

declare(strict_types=1);

namespace App\Company\Application\Command\UpdateCompany;

use App\Company\Application\Command\UpdateCompany\DTO\UpdateCompanyDTO;
use Symfony\Component\Uid\Uuid;

class UpdateCompanyCommand
{
    public function __construct(
        public readonly Uuid $companyId,
        public readonly UpdateCompanyDTO $updateCompanyDTO,
    ) {
    }
}
