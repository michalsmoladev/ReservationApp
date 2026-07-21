<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetServiceAvailability;

use Symfony\Component\Uid\Uuid;

final readonly class GetServiceAvailabilityQuery
{
    public function __construct(
        public Uuid $serviceId,
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
    }
}
