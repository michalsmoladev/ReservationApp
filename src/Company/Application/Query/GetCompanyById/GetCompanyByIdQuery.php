<?php

declare(strict_types=1);

namespace App\Company\Application\Query\GetCompanyById;

use Symfony\Component\Uid\Uuid;

class GetCompanyByIdQuery
{
    public function __construct(
        public readonly Uuid $companyId,
    ) {
    }
}
