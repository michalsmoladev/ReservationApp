<?php

namespace App\User\Application\Command\ActivateTenant;

use Symfony\Component\Uid\Uuid;

class ActivateTenantCommand
{
    public function __construct(
        public readonly Uuid $token,
    ) {
    }
}