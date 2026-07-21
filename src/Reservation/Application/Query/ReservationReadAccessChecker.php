<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Service;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\User;
use Symfony\Component\Uid\Uuid;

class ReservationReadAccessChecker
{
    /**
     * @return Uuid[]
     */
    public function resolveAccessibleCompanyIds(User $user): array
    {
        if ($user instanceof Tenant) {
            $companyIds = [];

            foreach ($user->getCompanies() as $company) {
                $companyIds[] = $company->getId();
            }

            return $companyIds;
        }

        if ($user instanceof Employee && null !== $user->getCompany()) {
            return [$user->getCompany()->getId()];
        }

        return [];
    }

    public function resolveAccessibleCompanyAddressId(User $user): ?Uuid
    {
        if ($user instanceof Employee) {
            return $user->getCompanyAddress()?->getId();
        }

        return null;
    }

    public function resolveAccessibleCustomerId(User $user): ?Uuid
    {
        if ($user instanceof Customer) {
            return $user->getUuid();
        }

        return null;
    }

    public function canViewReservation(User $user, Reservation $reservation, ?Service $service): bool
    {
        if ($user instanceof Customer) {
            return null !== $reservation->getCustomerId() && $reservation->getCustomerId()->equals($user->getUuid());
        }

        if (!$service) {
            return false;
        }

        if ($user instanceof Tenant) {
            foreach ($user->getCompanies() as $company) {
                if ($company->getId()->equals($service->getCompany()->getId())) {
                    return true;
                }
            }

            return false;
        }

        if ($user instanceof Employee) {
            return null !== $user->getCompany()
                && null !== $user->getCompanyAddress()
                && $user->getCompany()->getId()->equals($service->getCompany()->getId())
                && $user->getCompanyAddress()->getId()->equals($service->getCompanyAddress()->getId());
        }

        return false;
    }
}
