<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\DTO;

class AvailabilitySlotDTO
{
    /**
     * @param string[] $employeeIds
     */
    public function __construct(
        public string $startsAt,
        public string $endsAt,
        public array $employeeIds,
    ) {
    }
}
