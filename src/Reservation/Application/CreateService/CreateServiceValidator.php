<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;

#[AsMessageValidator]
class CreateServiceValidator
{
    public function __invoke(CreateServiceCommand $command): void
    {
        $serviceDTO = $command->serviceDTO;

        if ('' === trim($serviceDTO->name)) {
            throw new ValidationFail('[CreateService] Name cannot be empty');
        }

        if ($serviceDTO->duration <= 0) {
            throw new ValidationFail('[CreateService] Duration must be greater than zero');
        }

        if ($serviceDTO->price < 0) {
            throw new ValidationFail('[CreateService] Price cannot be negative');
        }
    }
}
