<?php

declare(strict_types=1);

namespace App\Company\Application\Command\UpdateCompany;

use App\Company\Domain\Entity\CompanyRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateCompanyHandler
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateCompanyCommand $command): void
    {
        $company = $this->companyRepository->findById($command->companyId);

        if (!$company) {
            throw new \RuntimeException('[UpdateCompany] Company not found during update');
        }

        $company->update(
            displayName: trim($command->updateCompanyDTO->displayName),
            legalName: trim($command->updateCompanyDTO->legalName),
            taxId: trim($command->updateCompanyDTO->taxId),
            currency: strtoupper(trim($command->updateCompanyDTO->currency)),
        );

        $this->companyRepository->save($company);

        $this->logger->info('[UpdateCompany] Company updated', [
            'company_id' => $company->getId()->toString(),
        ]);
    }
}
