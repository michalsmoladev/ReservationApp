<?php

namespace App\User\Application\Factory;

use App\User\Application\Command\CreateTenant\DTO\CreateTenantDTO;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\UserMetadata;
use Symfony\Component\Uid\Uuid;

class TenantFactory
{
    public function create(Uuid $id, CreateTenantDTO $tenantDTO): Tenant
    {
        $metadata = new UserMetadata(
            activationToken: Uuid::v7()->toString(),
            activationExpiresAt: new \DateTimeImmutable('+2 hours'),
        );

        $tenant = new Tenant(
            email: $tenantDTO->email,
            password: $tenantDTO->password,
            metadata: $metadata,
            isActive: false,
            firstname: $tenantDTO->firstname,
            lastname: $tenantDTO->lastname,
        );

        $tenant->setUuid($id);
        $tenant->setRoles(['ROLE_TENANT']);

        return $tenant;
    }
}