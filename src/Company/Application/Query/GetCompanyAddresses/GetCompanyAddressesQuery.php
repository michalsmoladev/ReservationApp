<?php

declare(strict_types=1);

namespace App\Company\Application\Query\GetCompanyAddresses;

use Symfony\Component\Uid\Uuid;

class GetCompanyAddressesQuery
{
    public function __construct(
        public readonly Uuid $companyId,
    ) {
    }
}
