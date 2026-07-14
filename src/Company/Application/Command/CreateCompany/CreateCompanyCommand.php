<?php

declare(strict_types=1);

namespace App\Company\Application\Command\CreateCompany;

use App\Company\Application\Command\CreateCompany\DTO\CreateCompanyDTO;
use Symfony\Component\Uid\Uuid;

class CreateCompanyCommand
{
    public Uuid $id;
    public Uuid $tenantId;

    public function __construct(
        public CreateCompanyDTO $companyDTO,
    ) {
    }
}
