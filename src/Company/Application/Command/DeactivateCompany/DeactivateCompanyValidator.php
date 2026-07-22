<?php

declare(strict_types=1);

namespace App\Company\Application\Command\DeactivateCompany;

use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\CompanyOpeningHour\CompanyOpeningHourRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMessageValidator]
class DeactivateCompanyValidator
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly CompanyOpeningHourRepositoryInterface $companyOpeningHourRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(DeactivateCompanyCommand $command): void
    {
        $company = $this->companyRepository->findById($command->companyId);

        if (!$company || !$company->isActive()) {
            throw new ValidationFail('[DeactivateCompany] Company not found');
        }

        $user = $this->security->getUser();

        if (!$user instanceof Tenant) {
            throw new ValidationFail('[DeactivateCompany] Only tenant can deactivate company');
        }

        $ownsCompany = false;

        foreach ($user->getCompanies() as $tenantCompany) {
            if ($tenantCompany->getId()->equals($company->getId())) {
                $ownsCompany = true;
                break;
            }
        }

        if (!$ownsCompany) {
            throw new ValidationFail('[DeactivateCompany] Tenant cannot deactivate foreign company');
        }

        if ($this->serviceRepository->existsActiveByCompanyId($company->getId())) {
            throw new ValidationFail('[DeactivateCompany] Company has active services');
        }

        if ($this->employeeRepository->existsActiveByCompanyId($company->getId())) {
            throw new ValidationFail('[DeactivateCompany] Company has active employees');
        }

        if ($this->reservationRepository->existsActiveByCompanyId($company->getId())) {
            throw new ValidationFail('[DeactivateCompany] Company has active reservations');
        }

        if ($this->companyOpeningHourRepository->existsByCompanyId($company->getId())) {
            throw new ValidationFail('[DeactivateCompany] Company has opening hours');
        }
    }
}
