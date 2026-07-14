<?php

declare(strict_types=1);

namespace App\Company\Application\Command\CreateCompany;

use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;

#[AsMessageValidator]
class CreateCompanyValidator
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly TenantRepositoryInterface $tenantRepository,
    ) {
    }

    public function __invoke(CreateCompanyCommand $command): void
    {
        $company = $this->companyRepository->findByName($command->companyDTO->displayName);

        if ($company) {
            throw new ValidationFail('Company already exists.');
        }

        $tenant = $this->tenantRepository->findById($command->tenantId);

        if (!$tenant) {
            throw new ValidationFail('Tenant not found.');
        }
    }
}
