<?php

namespace App\User\Application\Command\UpdateTenant;

use App\User\Application\Command\UpdateTenant\DTO\UpdateTenantDTO;
use Symfony\Component\Uid\Uuid;

class UpdateTenantCommand
{
    public function __construct(
        public Uuid $id,
        public UpdateTenantDTO $tenantDTO,
    ) {
    }
}