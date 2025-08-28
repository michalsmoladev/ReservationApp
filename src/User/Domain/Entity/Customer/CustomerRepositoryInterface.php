<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Customer;

use Symfony\Component\Uid\Uuid;

interface CustomerRepositoryInterface
{
    public function save(Customer $customer): void;

    public function findById(Uuid $id): ?Customer;

    public function findByEmail(string $email): ?Customer;

    public function remove(Customer $customer): void;
}