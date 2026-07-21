<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetReservations;

use Symfony\Component\Uid\Uuid;

final readonly class GetReservationsQuery
{
    public function __construct(
        public ?Uuid $companyId = null,
        public ?Uuid $employeeId = null,
        public ?Uuid $customerId = null,
        public ?\DateTimeImmutable $from = null,
        public ?\DateTimeImmutable $to = null,
        public ?string $status = null,
    ) {
    }
}
