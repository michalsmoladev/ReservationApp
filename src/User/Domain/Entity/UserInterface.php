<?php

declare(strict_types=1);

namespace App\User\Domain\Entity;

use Symfony\Component\Uid\Uuid;

interface UserInterface
{
    public function findByEmail(string $email): ?User;

    public function findByUuid(Uuid $uuid): ?User;
    public function save(User $user): void;
    public function lock(Uuid $uuid): void;
    public function remove(User $user): void;
    public function findByToken(string $token): ?User;
}