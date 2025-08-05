<?php

declare(strict_types=1);

namespace App\User\Domain\Entity\Employee;

use Symfony\Component\Uid\Uuid;

interface EmployeeRepositoryInterface
{
    public function findByEmail(string $email): ?Employee;
    public function findById(Uuid $uuid): ?Employee;
    public function save(Employee $employee): void;
    public function lock(Uuid $uuid): void;
    public function remove(Employee $Employee): void;
    public function findByToken(string $token): ?Employee;
}