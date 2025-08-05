<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateService;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;

#[AsMessageValidator]
class CreateServiceValidator
{
    public function __invoke(): void
    {

    }
}