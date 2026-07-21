<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateCompanyOpeningHour;

use App\Reservation\Application\CreateCompanyOpeningHour\DTO\CreateCompanyOpeningHourDTO;

class CreateCompanyOpeningHourCommand
{
    public function __construct(
        public readonly CreateCompanyOpeningHourDTO $createCompanyOpeningHourDTO,
    ) {
    }
}
