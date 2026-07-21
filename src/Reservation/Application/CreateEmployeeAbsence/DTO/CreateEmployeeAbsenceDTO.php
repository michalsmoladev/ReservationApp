<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateEmployeeAbsence\DTO;

class CreateEmployeeAbsenceDTO
{
    public function __construct(
        public string $employeeId,
        public string $startsAt,
        public string $endsAt,
        public string $reason,
    ) {
    }
}
