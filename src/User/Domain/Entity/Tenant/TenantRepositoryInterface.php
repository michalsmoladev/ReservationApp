<?php

namespace App\User\Domain\Entity\Tenant;

use Symfony\Component\Uid\Uuid;

interface TenantRepositoryInterface
{
    public function findByEmail(string $email): ?Tenant;
    public function findById(Uuid $uuid): ?Tenant;
    public function save(Tenant $employee): void;
    public function lock(Uuid $uuid): void;
    public function remove(Tenant $Employee): void;
    public function findByToken(string $token): ?Tenant;
}