<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetCustomerById;

use Symfony\Component\Uid\Uuid;

class GetCustomerByIdQuery
{
    public function __construct(
        public Uuid $customerId,
    ) {
    }
}