<?php

declare(strict_types=1);

namespace App\Company\Application\Command\DeactivateCompany;

use App\Company\Domain\Entity\CompanyRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeactivateCompanyHandler
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeactivateCompanyCommand $command): void
    {
        $company = $this->companyRepository->findById($command->companyId);

        if (!$company) {
            throw new \RuntimeException('[DeactivateCompany] Company not found during deactivation');
        }

        $company->deactivate();
        $this->companyRepository->save($company);

        $this->logger->info('[DeactivateCompany] Company deactivated', [
            'company_id' => $company->getId()->toString(),
        ]);
    }
}
