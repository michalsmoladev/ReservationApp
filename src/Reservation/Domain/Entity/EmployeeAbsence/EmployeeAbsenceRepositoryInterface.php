<?php

declare(strict_types=1);

namespace App\Reservation\Domain\Entity\EmployeeAbsence;

use App\Reservation\Domain\Entity\EmployeeAbsence;
use Symfony\Component\Uid\Uuid;

interface EmployeeAbsenceRepositoryInterface
{
    public function save(EmployeeAbsence $employeeAbsence): void;

    public function hasOverlap(
        Uuid $employeeId,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
    ): bool;

    /**
     * @return EmployeeAbsence[]
     */
    public function findByEmployeeAndDateRange(
        Uuid $employeeId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array;
}
