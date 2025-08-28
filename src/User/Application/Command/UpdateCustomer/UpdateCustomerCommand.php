<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateCustomer;

use App\User\Application\Command\UpdateCustomer\DTO\UpdateCustomerDTO;
use Symfony\Component\Uid\Uuid;

class UpdateCustomerCommand
{
    public function __construct(
        public Uuid $customerId,
        public UpdateCustomerDTO $dto
    ) {
    }
}