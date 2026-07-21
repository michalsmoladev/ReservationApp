<?php

declare(strict_types=1);

namespace App\Company\Application\Command\UpdateCompanyAddress;

use App\Company\Application\Command\UpdateCompanyAddress\DTO\UpdateCompanyAddressDTO;
use Symfony\Component\Uid\Uuid;

class UpdateCompanyAddressCommand
{
    public function __construct(
        public readonly Uuid $companyAddressId,
        public readonly UpdateCompanyAddressDTO $updateCompanyAddressDTO,
    ) {
    }
}
