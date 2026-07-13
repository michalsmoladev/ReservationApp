<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

use App\Reservation\Application\CreateService\DTO\CreateServiceDTO;
use Symfony\Component\Uid\Uuid;

class CreateServiceCommand
{
    public function __construct(
        public CreateServiceDTO $createServiceDTO,
        public Uuid $id,
    ) {
    }
}
