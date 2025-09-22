<?php

namespace App\User\Application\Query\GetTenantById;

use Symfony\Component\Uid\Uuid;

class GetTenantByIdQuery
{
    public function __construct(
        public Uuid $id,
    ) {
    }
}