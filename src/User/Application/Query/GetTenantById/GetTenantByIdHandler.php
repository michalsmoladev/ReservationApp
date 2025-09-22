<?php

namespace App\User\Application\Query\GetTenantById;

use App\User\Application\Exception\TenantNotFoundException;
use App\User\Application\Query\DTO\TenantDTO;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use App\User\Domain\Service\TenantService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetTenantByIdHandler
{
    public function __construct(
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly TenantService $tenantService,
    ) {
    }

    public function __invoke(GetTenantByIdQuery $query): TenantDTO
    {
        $tenant = $this->tenantRepository->findById($query->id);

        if (!$tenant) {
            throw new TenantNotFoundException();
        }

        return $this->tenantService->createDtoFromEntity($tenant);
    }
}