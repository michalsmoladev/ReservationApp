<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\DTO;

class ReservationDetailsDTO
{
    public function __construct(
        public string $id,
        public string $reservationDate,
        public string $status,
        public string $serviceId,
        public ?string $serviceName,
        public ?string $companyId,
        public ?string $companyName,
        public ?string $companyAddressId,
        public ?string $employeeId,
        public ?string $employeeFirstname,
        public ?string $employeeLastname,
        public ?string $customerId,
        public ?string $customerFirstname,
        public ?string $customerLastname,
        public ?string $guestFirstname,
        public ?string $guestLastname,
        public ?string $guestEmail,
        public ?string $guestPhone,
        public float $servicePrice,
        public float $serviceDuration,
        public ?string $note,
        public string $createdAt,
        public ?string $updatedAt,
    ) {
    }
}
