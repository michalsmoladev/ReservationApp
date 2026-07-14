<?php

declare(strict_types=1);

namespace App\Company\Domain\Entity\Address;

use Symfony\Component\Uid\Uuid;

interface CompanyAddressRepositoryInterface
{
    public function findById(Uuid $id): ?CompanyAddress;
}
