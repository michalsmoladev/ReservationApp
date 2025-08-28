<?php

declare(strict_types=1);

namespace App\User\Application\Command\RemoveCustomer;

use Symfony\Component\Uid\Uuid;

class RemoveCustomerCommand
{
    public function __construct(
        public Uuid $customerId,
    ) {
    }
}