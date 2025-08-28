<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateCustomer\DTO;

class UpdateCustomerDTO
{
    public function __construct(
        public string $email,
        public string $password,
        public string $firstname,
        public string $lastname,
        public string $phone,
    ) {
    }
}