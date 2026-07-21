<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetServices;

use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Application\Query\DTO\ServiceCollectionDTO;
use App\Reservation\Application\Query\ServiceQueryDataProvider;
use App\Reservation\Application\ServiceAccessChecker;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetServicesHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly ServiceAccessChecker $serviceAccessChecker,
        private readonly ServiceQueryDataProvider $serviceQueryDataProvider,
        private readonly Security $security,
    ) {
    }

    public function __invoke(GetServicesQuery $query): ServiceCollectionDTO
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[GetServices] Authenticated user is required');
        }

        if (!$user instanceof Tenant && !$user instanceof Employee) {
            throw new ValidationFail('[GetServices] Only tenant or employee can list services');
        }

        if ($this->isOutOfScopeQuery($user, $query)) {
            return new ServiceCollectionDTO(services: []);
        }

        $effectiveCompanyId = $query->companyId;
        $effectiveCompanyAddressId = $query->companyAddressId;

        if ($user instanceof Employee) {
            $effectiveCompanyId = $user->getCompany()?->getId();
            $effectiveCompanyAddressId = $user->getCompanyAddress()?->getId();
        }

        $services = $this->serviceRepository->findByFilters(
            companyId: $effectiveCompanyId,
            companyAddressId: $effectiveCompanyAddressId,
            onlyActive: true,
        );

        $items = [];

        foreach ($services as $service) {
            if (!$this->serviceAccessChecker->canManageService($user, $service)) {
                continue;
            }

            $items[] = $this->serviceQueryDataProvider->mapServiceToDto($service);
        }

        return new ServiceCollectionDTO(services: $items);
    }

    private function isOutOfScopeQuery(User $user, GetServicesQuery $query): bool
    {
        if ($user instanceof Tenant && null !== $query->companyId) {
            foreach ($user->getCompanies() as $company) {
                if ($company->getId()->equals($query->companyId)) {
                    return false;
                }
            }

            return true;
        }

        if ($user instanceof Employee) {
            if (
                null !== $query->companyId
                && (
                    null === $user->getCompany()
                    || !$user->getCompany()->getId()->equals($query->companyId)
                )
            ) {
                return true;
            }

            if (
                null !== $query->companyAddressId
                && (
                    null === $user->getCompanyAddress()
                    || !$user->getCompanyAddress()->getId()->equals($query->companyAddressId)
                )
            ) {
                return true;
            }
        }

        return false;
    }
}
