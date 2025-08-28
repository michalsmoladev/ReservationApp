<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateCustomer;

use App\User\Application\Command\CreateCustomer\DTO\CreateCustomerDTO;
use Symfony\Component\Uid\Uuid;

class CreateCustomerCommand
{
    public Uuid $id;

    public function __construct(
        public CreateCustomerDTO $createCustomerDTO,
    ) {
    }
}