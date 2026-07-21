<?php

declare(strict_types=1);

namespace App\Reservation\Application;

use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Company;
use App\Reservation\Domain\Entity\Service;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\User;

class ServiceAccessChecker
{
    public function canManageService(User $user, Service $service): bool
    {
        return $this->canManageCompanyAddress($user, $service->getCompany(), $service->getCompanyAddress());
    }

    public function canManageCompanyAddress(User $user, Company $company, CompanyAddress $companyAddress): bool
    {
        if ($user instanceof Tenant) {
            foreach ($user->getCompanies() as $tenantCompany) {
                if ($tenantCompany->getId()->equals($company->getId())) {
                    return true;
                }
            }

            return false;
        }

        if ($user instanceof Employee) {
            return null !== $user->getCompany()
                && null !== $user->getCompanyAddress()
                && $user->getCompany()->getId()->equals($company->getId())
                && $user->getCompanyAddress()->getId()->equals($companyAddress->getId());
        }

        return false;
    }
}
