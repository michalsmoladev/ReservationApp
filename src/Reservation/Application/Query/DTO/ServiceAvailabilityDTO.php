<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\DTO;

class ServiceAvailabilityDTO
{
    /**
     * @param AvailabilitySlotDTO[] $slots
     */
    public function __construct(
        public string $serviceId,
        public string $from,
        public string $to,
        public array $slots,
    ) {
    }
}
