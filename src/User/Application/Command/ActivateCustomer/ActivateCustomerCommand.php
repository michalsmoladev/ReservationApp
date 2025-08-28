<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateCustomer;

use Symfony\Component\Uid\Uuid;

class ActivateCustomerCommand
{
    public function __construct(
        public readonly Uuid $token,
    ) {
    }
}