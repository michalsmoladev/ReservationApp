<?php

declare(strict_types=1);

namespace App\User\Domain;

use App\User\Application\Query\DTO\UserDTO;
use App\User\Domain\Entity\User;

class UserService
{
    public function makeDtoFromEntity(User $user): UserDTO
    {
        return new UserDTO(
            email: $user->getEmail(),
            roles: $user->getRoles(),
            createdAt: $user->getCreatedAt()->format(DATE_ATOM),
            updatedAt: $user->getUpdatedAt()?->format(DATE_ATOM),
        );
    }
}