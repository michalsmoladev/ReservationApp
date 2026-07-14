<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

use App\Reservation\Application\CreateService\DTO\CreateServiceDTO;
use Symfony\Component\Uid\Uuid;

class CreateServiceCommand
{
    public Uuid $id;

    public function __construct(
        public CreateServiceDTO $serviceDTO,
    ) {
    }
}
