<?php

declare(strict_types=1);

namespace App\Company\Domain\Entity;

use Symfony\Component\Uid\Uuid;

interface CompanyRepositoryInterface
{
    public function save(Company $company): void;

    public function findById(Uuid $id): ?Company;

    public function findByName(string $name): ?Company;
}
