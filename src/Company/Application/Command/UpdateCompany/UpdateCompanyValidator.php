<?php

declare(strict_types=1);

namespace App\Company\Application\Command\UpdateCompany;

use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMessageValidator]
class UpdateCompanyValidator
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(UpdateCompanyCommand $command): void
    {
        $company = $this->companyRepository->findById($command->companyId);

        if (!$company || !$company->isActive()) {
            throw new ValidationFail('[UpdateCompany] Company not found');
        }

        $user = $this->security->getUser();

        if (!$user instanceof Tenant) {
            throw new ValidationFail('[UpdateCompany] Only tenant can update company');
        }

        $ownsCompany = false;

        foreach ($user->getCompanies() as $tenantCompany) {
            if ($tenantCompany->getId()->equals($company->getId())) {
                $ownsCompany = true;
                break;
            }
        }

        if (!$ownsCompany) {
            throw new ValidationFail('[UpdateCompany] Tenant cannot update foreign company');
        }

        if ('' === trim($command->updateCompanyDTO->displayName)) {
            throw new ValidationFail('[UpdateCompany] Display name cannot be blank');
        }

        if ('' === trim($command->updateCompanyDTO->legalName)) {
            throw new ValidationFail('[UpdateCompany] Legal name cannot be blank');
        }

        if ('' === trim($command->updateCompanyDTO->taxId)) {
            throw new ValidationFail('[UpdateCompany] Tax id cannot be blank');
        }

        $currency = strtoupper(trim($command->updateCompanyDTO->currency));

        if (3 !== strlen($currency)) {
            throw new ValidationFail('[UpdateCompany] Currency must be a 3-letter code');
        }
    }
}
