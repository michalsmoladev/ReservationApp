<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity\CompanyOpeningHour;

use App\Reservation\Domain\Entity\CompanyOpeningHour;
use Symfony\Component\Uid\Uuid;

interface CompanyOpeningHourRepositoryInterface
{
    public function save(CompanyOpeningHour $companyOpeningHour): void;

    public function existsForDay(
        Uuid $companyId,
        int $dayOfWeek,
        ?Uuid $companyAddressId = null,
    ): bool;

    /**
     * @return CompanyOpeningHour[]
     */
    public function findByCompanyAndDateRange(
        Uuid $companyId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        ?Uuid $companyAddressId = null,
    ): array;
}
