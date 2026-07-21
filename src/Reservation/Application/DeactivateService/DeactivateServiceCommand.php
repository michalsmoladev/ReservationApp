<?php

declare(strict_types=1);

namespace App\Reservation\Application\DeactivateService;

use Symfony\Component\Uid\Uuid;

class DeactivateServiceCommand
{
    public function __construct(
        public readonly Uuid $serviceId,
    ) {
    }
}
