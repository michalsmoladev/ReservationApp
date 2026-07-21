<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetServiceById;

use Symfony\Component\Uid\Uuid;

final readonly class GetServiceByIdQuery
{
    public function __construct(
        public Uuid $serviceId,
    ) {
    }
}
