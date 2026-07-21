<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateEmployeeAbsence;

use App\Reservation\Application\CreateEmployeeAbsence\DTO\CreateEmployeeAbsenceDTO;

class CreateEmployeeAbsenceCommand
{
    public function __construct(
        public readonly CreateEmployeeAbsenceDTO $createEmployeeAbsenceDTO,
    ) {
    }
}
