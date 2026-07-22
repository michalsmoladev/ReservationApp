<?php

declare(strict_types=1);

namespace App\Company\Application\Command\CreateCompanyAddress;

use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMessageValidator]
class CreateCompanyAddressValidator
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(CreateCompanyAddressCommand $command): void
    {
        $company = $this->companyRepository->findById($command->companyId);

        if (!$company || !$company->isActive()) {
            throw new ValidationFail('[CreateCompanyAddress] Company not found');
        }

        $user = $this->security->getUser();

        if (!$user instanceof Tenant) {
            throw new ValidationFail('[CreateCompanyAddress] Only tenant can create company address');
        }

        $ownsCompany = false;

        foreach ($user->getCompanies() as $tenantCompany) {
            if ($tenantCompany->getId()->equals($company->getId())) {
                $ownsCompany = true;
                break;
            }
        }

        if (!$ownsCompany) {
            throw new ValidationFail('[CreateCompanyAddress] Tenant cannot create address for foreign company');
        }

        $dto = $command->createCompanyAddressDTO;

        foreach ([
            'street' => $dto->street,
            'city' => $dto->city,
            'postalCode' => $dto->postalCode,
            'country' => $dto->country,
        ] as $field => $value) {
            if ('' === trim($value)) {
                throw new ValidationFail(sprintf('[CreateCompanyAddress] %s cannot be blank', $field));
            }
        }

        if ($dto->apartmentNo < 0) {
            throw new ValidationFail('[CreateCompanyAddress] Apartment number cannot be negative');
        }

        if ($dto->buildingNo <= 0) {
            throw new ValidationFail('[CreateCompanyAddress] Building number must be greater than zero');
        }
    }
}
