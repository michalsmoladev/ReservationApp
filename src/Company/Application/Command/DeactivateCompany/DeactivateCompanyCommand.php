<?php

declare(strict_types=1);

namespace App\Company\Application\Command\DeactivateCompany;

use Symfony\Component\Uid\Uuid;

class DeactivateCompanyCommand
{
    public function __construct(
        public readonly Uuid $companyId,
    ) {
    }
}
