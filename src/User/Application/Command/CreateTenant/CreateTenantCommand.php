<?php

namespace App\User\Application\Command\CreateTenant;

use App\User\Application\Command\CreateTenant\DTO\CreateTenantDTO;
use Symfony\Component\Uid\Uuid;

class CreateTenantCommand
{
    public Uuid $id;

    public function __construct(
        public CreateTenantDTO $tenantDTO,
    ) {
    }
}