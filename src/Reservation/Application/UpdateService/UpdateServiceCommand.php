<?php

declare(strict_types=1);

namespace App\Reservation\Application\UpdateService;

use App\Reservation\Application\UpdateService\DTO\UpdateServiceDTO;
use Symfony\Component\Uid\Uuid;

class UpdateServiceCommand
{
    public function __construct(
        public readonly Uuid $serviceId,
        public readonly UpdateServiceDTO $updateServiceDTO,
    ) {
    }
}
