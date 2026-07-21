<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateEmployeeWorkingHour\DTO;

class CreateEmployeeWorkingHourDTO
{
    public function __construct(
        public string $employeeId,
        public int $dayOfWeek,
        public string $startsAt,
        public string $endsAt,
    ) {
    }
}
