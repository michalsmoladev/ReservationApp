<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateCompanyOpeningHour\DTO;

class CreateCompanyOpeningHourDTO
{
    public function __construct(
        public string $companyId,
        public ?string $companyAddressId,
        public int $dayOfWeek,
        public ?string $opensAt,
        public ?string $closesAt,
        public bool $isClosed,
    ) {
    }
}
