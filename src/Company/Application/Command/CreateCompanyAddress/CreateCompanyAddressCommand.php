<?php

declare(strict_types=1);

namespace App\Company\Application\Command\CreateCompanyAddress;

use App\Company\Application\Command\CreateCompanyAddress\DTO\CreateCompanyAddressDTO;
use Symfony\Component\Uid\Uuid;

class CreateCompanyAddressCommand
{
    public function __construct(
        public readonly Uuid $companyId,
        public readonly Uuid $addressId,
        public readonly CreateCompanyAddressDTO $createCompanyAddressDTO,
    ) {
    }
}
