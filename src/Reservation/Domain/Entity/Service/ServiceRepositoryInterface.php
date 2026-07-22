<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity\Service;

use App\Reservation\Domain\Entity\Service;
use Symfony\Component\Uid\Uuid;

interface ServiceRepositoryInterface
{
    public function save(Service $service): void;

    public function findById(Uuid $id): ?Service;

    /**
     * @return Service[]
     */
    public function findByFilters(
        ?Uuid $companyId,
        ?Uuid $companyAddressId,
        bool $onlyActive = true,
    ): array;

    /**
     * @param Uuid[] $ids
     * @return Service[]
     */
    public function findByIds(array $ids): array;

    public function existsActiveByCompanyId(Uuid $companyId): bool;
}
