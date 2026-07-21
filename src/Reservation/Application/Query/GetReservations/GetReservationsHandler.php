<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetReservations;

use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Application\Query\DTO\ReservationCollectionDTO;
use App\Reservation\Application\Query\ReservationQueryDataProvider;
use App\Reservation\Application\Query\ReservationReadAccessChecker;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler(bus: 'query.bus')]
class GetReservationsHandler
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ReservationQueryDataProvider $reservationQueryDataProvider,
        private readonly ReservationReadAccessChecker $reservationReadAccessChecker,
        private readonly Security $security,
    ) {
    }

    public function __invoke(GetReservationsQuery $query): ReservationCollectionDTO
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[GetReservations] Authenticated user is required');
        }

        if ($this->isOutOfScopeQuery($user, $query)) {
            return new ReservationCollectionDTO(reservations: []);
        }

        if ($user instanceof Employee && null === $user->getCompanyAddress()) {
            return new ReservationCollectionDTO(reservations: []);
        }

        $effectiveCompanyId = $this->resolveEffectiveCompanyId($user, $query->companyId);
        $effectiveCompanyIds = $this->resolveEffectiveCompanyIds($user, $effectiveCompanyId);
        $effectiveCustomerId = $this->resolveEffectiveCustomerId($user, $query->customerId);

        $reservations = $this->reservationRepository->findByFilters(
            companyId: $effectiveCompanyId,
            companyAddressId: $this->reservationReadAccessChecker->resolveAccessibleCompanyAddressId($user),
            employeeId: $query->employeeId,
            customerId: $effectiveCustomerId,
            from: $query->from,
            to: $query->to,
            status: $query->status,
            companyIds: $effectiveCompanyIds,
        );

        $services = $this->reservationQueryDataProvider->loadServicesByReservation($reservations);
        $employees = $this->reservationQueryDataProvider->loadEmployeesByReservation($reservations);
        $customers = $this->reservationQueryDataProvider->loadCustomersByReservation($reservations);
        $items = [];

        foreach ($reservations as $reservation) {
            $service = $services[$reservation->getServiceId()->toString()] ?? null;

            if (!$this->reservationReadAccessChecker->canViewReservation($user, $reservation, $service)) {
                continue;
            }

            $items[] = $this->reservationQueryDataProvider->mapReservationToDto(
                reservation: $reservation,
                services: $services,
                employees: $employees,
                customers: $customers,
            );
        }

        return new ReservationCollectionDTO(reservations: $items);
    }

    private function isOutOfScopeQuery(User $user, GetReservationsQuery $query): bool
    {
        if ($user instanceof Tenant && null !== $query->companyId) {
            foreach ($user->getCompanies() as $company) {
                if ($company->getId()->equals($query->companyId)) {
                    return false;
                }
            }

            return true;
        }

        if (
            $user instanceof Employee
            && null !== $query->companyId
            && (
                null === $user->getCompany()
                || !$user->getCompany()->getId()->equals($query->companyId)
            )
        ) {
            return true;
        }

        return $user instanceof Customer
            && null !== $query->customerId
            && !$query->customerId->equals($user->getUuid());
    }

    private function resolveEffectiveCompanyId(User $user, ?Uuid $requestedCompanyId): ?Uuid
    {
        if ($user instanceof Employee) {
            return $user->getCompany()?->getId();
        }

        if (!$user instanceof Tenant) {
            return $requestedCompanyId;
        }

        if (null === $requestedCompanyId) {
            return null;
        }

        return $requestedCompanyId;
    }

    /**
     * @return Uuid[]|null
     */
    private function resolveEffectiveCompanyIds(User $user, ?Uuid $effectiveCompanyId): ?array
    {
        if ($user instanceof Tenant) {
            if (null !== $effectiveCompanyId) {
                return [$effectiveCompanyId];
            }

            return $this->reservationReadAccessChecker->resolveAccessibleCompanyIds($user);
        }

        if ($user instanceof Employee) {
            return $this->reservationReadAccessChecker->resolveAccessibleCompanyIds($user);
        }

        return null;
    }

    private function resolveEffectiveCustomerId(User $user, ?Uuid $requestedCustomerId): ?Uuid
    {
        if (!$user instanceof Customer) {
            return $requestedCustomerId;
        }

        if (null === $requestedCustomerId || $requestedCustomerId->equals($user->getUuid())) {
            return $user->getUuid();
        }

        return Uuid::v7();
    }
}
