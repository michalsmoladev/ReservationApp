<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateCustomer;

class ActivateCustomerCommand
{
    public function __construct(
        public readonly string $token,
    ) {
    }
}