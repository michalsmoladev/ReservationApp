<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity\EmployeeWorkingHour;

use App\Reservation\Domain\Entity\EmployeeWorkingHour;
use Symfony\Component\Uid\Uuid;

interface EmployeeWorkingHourRepositoryInterface
{
    public function save(EmployeeWorkingHour $employeeWorkingHour): void;

    public function existsForDay(Uuid $employeeId, int $dayOfWeek): bool;

    /**
     * @return EmployeeWorkingHour[]
     */
    public function findByEmployeeAndDateRange(
        Uuid $employeeId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;
}
