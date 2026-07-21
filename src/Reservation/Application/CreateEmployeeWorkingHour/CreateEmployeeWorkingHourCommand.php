<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateEmployeeWorkingHour;

use App\Reservation\Application\CreateEmployeeWorkingHour\DTO\CreateEmployeeWorkingHourDTO;

class CreateEmployeeWorkingHourCommand
{
    public function __construct(
        public readonly CreateEmployeeWorkingHourDTO $createEmployeeWorkingHourDTO,
    ) {
    }
}
