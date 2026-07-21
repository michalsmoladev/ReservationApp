<?php

declare(strict_types=1);

namespace App\Company\Application\Command\DeleteCompanyAddress;

use Symfony\Component\Uid\Uuid;

class DeleteCompanyAddressCommand
{
    public function __construct(
        public readonly Uuid $companyAddressId,
    ) {
    }
}
