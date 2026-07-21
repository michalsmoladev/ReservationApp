<?php

declare(strict_types=1);

namespace App\Company\Application\Command\UpdateCompanyAddress;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMessageValidator]
class UpdateCompanyAddressValidator
{
    public function __construct(
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(UpdateCompanyAddressCommand $command): void
    {
        $address = $this->companyAddressRepository->findById($command->companyAddressId);

        if (!$address) {
            throw new ValidationFail('[UpdateCompanyAddress] Company address not found');
        }

        $user = $this->security->getUser();

        if (!$user instanceof Tenant) {
            throw new ValidationFail('[UpdateCompanyAddress] Only tenant can update company address');
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
            throw new ValidationFail('[UpdateCompanyAddress] Tenant cannot update foreign company address');
        }

        $dto = $command->updateCompanyAddressDTO;

        foreach ([
            'street' => $dto->street,
            'city' => $dto->city,
            'postalCode' => $dto->postalCode,
            'country' => $dto->country,
        ] as $field => $value) {
            if ('' === trim($value)) {
                throw new ValidationFail(sprintf('[UpdateCompanyAddress] %s cannot be blank', $field));
            }
        }

        if ($dto->apartmentNo < 0) {
            throw new ValidationFail('[UpdateCompanyAddress] Apartment number cannot be negative');
        }

        if ($dto->buildingNo <= 0) {
            throw new ValidationFail('[UpdateCompanyAddress] Building number must be greater than zero');
        }
    }
}
