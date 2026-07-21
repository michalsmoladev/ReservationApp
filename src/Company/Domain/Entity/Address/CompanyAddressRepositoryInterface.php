<?php

declare(strict_types=1);

namespace App\Company\Domain\Entity\Address;

use Symfony\Component\Uid\Uuid;

interface CompanyAddressRepositoryInterface
{
    public function findById(Uuid $id): ?CompanyAddress;

    /**
     * @return CompanyAddress[]
     */
    public function findByCompanyId(Uuid $companyId): array;

    public function save(CompanyAddress $companyAddress): void;

    public function remove(CompanyAddress $companyAddress): void;

    public function isUsed(Uuid $companyAddressId): bool;
}
