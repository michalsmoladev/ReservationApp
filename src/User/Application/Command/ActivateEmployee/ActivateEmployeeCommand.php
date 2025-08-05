<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateEmployee;

class ActivateEmployeeCommand
{
    public function __construct(
        public readonly string $token,
    ) {
    }
}