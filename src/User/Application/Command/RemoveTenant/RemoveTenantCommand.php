<?php

namespace App\User\Application\Command\RemoveTenant;

use Symfony\Component\Uid\Uuid;

class RemoveTenantCommand
{
    public function __construct(
        public Uuid $id,
    ) {
    }
}