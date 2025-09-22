<?php

namespace App\User\Domain\Service;

use App\User\Application\Query\DTO\TenantDTO;
use App\User\Domain\Entity\Tenant\Tenant;

class TenantService
{
    public function createDtoFromEntity(Tenant $tenant): TenantDTO
    {
        return new TenantDTO(
            email: $tenant->getEmail(),
            roles: $tenant->getRoles(),
            firstname: $tenant->getFirstname(),
            lastname: $tenant->getLastname(),
            createdAt: $tenant->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            updatedAt: $tenant->getUpdatedAt()->format(\DateTimeImmutable::ATOM),
        );
    }
}