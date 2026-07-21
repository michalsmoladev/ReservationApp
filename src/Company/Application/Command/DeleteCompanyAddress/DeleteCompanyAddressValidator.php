<?php

declare(strict_types=1);

namespace App\Company\Application\Command\DeleteCompanyAddress;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMessageValidator]
class DeleteCompanyAddressValidator
{
    public function __construct(
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(DeleteCompanyAddressCommand $command): void
    {
        $address = $this->companyAddressRepository->findById($command->companyAddressId);

        if (!$address) {
            throw new ValidationFail('[DeleteCompanyAddress] Company address not found');
        }

        $user = $this->security->getUser();

        if (!$user instanceof Tenant) {
            throw new ValidationFail('[DeleteCompanyAddress] Only tenant can delete company address');
        }

        $company = $address->getCompany();
        $ownsCompany = false;

        foreach ($user->getCompanies() as $tenantCompany) {
            if (null !== $company && $tenantCompany->getId()->equals($company->getId())) {
                $ownsCompany = true;
                break;
            }
        }

        if (!$ownsCompany) {
            throw new ValidationFail('[DeleteCompanyAddress] Tenant cannot delete foreign company address');
        }

        if ($this->companyAddressRepository->isUsed($command->companyAddressId)) {
            throw new ValidationFail('[DeleteCompanyAddress] Company address is still used by employees, services or opening hours');
        }
    }
}
