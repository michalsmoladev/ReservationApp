<?php

declare(strict_types=1);

namespace App\Company\Application\Command\CreateCompany;

use App\Company\Application\Factory\CompanyFactory;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateCompanyHandler
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyFactory $companyFactory,
        private readonly TenantRepositoryInterface $tenantRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateCompanyCommand $command): void
    {
        $tenant = $this->tenantRepository->findById($command->tenantId);

        if (!$tenant) {
            throw new \RuntimeException('[CreateCompany] Tenant not found during company creation');
        }

        $company = $this->companyFactory->create(
            id: $command->id,
            companyDTO: $command->companyDTO,
        );

        $tenant->addCompany($company);

        $this->companyRepository->save($company);
        $this->tenantRepository->save($tenant);

        $this->logger->info('[CreateCompany] Company created', [
            'company_id' => $company->getId()->toString(),
            'tenant_id' => $tenant->getUuid()->toString(),
        ]);
    }
}
