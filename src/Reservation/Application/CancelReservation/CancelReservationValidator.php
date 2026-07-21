<?php

declare(strict_types=1);

namespace App\Reservation\Application\CancelReservation;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation\ReservationStatusEnum;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMessageValidator]
class CancelReservationValidator
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(CancelReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->findById($command->reservationId);

        if (!$reservation) {
            throw new ValidationFail('[CancelReservation] Reservation not found');
        }

        if ($reservation->getStatus() === ReservationStatusEnum::CANCELED->value) {
            throw new ValidationFail('[CancelReservation] Reservation is already canceled');
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[CancelReservation] Authenticated user is required');
        }

        if ($user instanceof Customer) {
            if (!$reservation->getCustomerId() || !$reservation->getCustomerId()->equals($user->getUuid())) {
                throw new ValidationFail('[CancelReservation] Customer can cancel only their own reservations');
            }

            return;
        }

        $service = $this->serviceRepository->findById($reservation->getServiceId());

        if (!$service) {
            throw new ValidationFail('[CancelReservation] Service not found for reservation');
        }

        if ($user instanceof Tenant) {
            foreach ($user->getCompanies() as $company) {
                if ($company->getId()->equals($service->getCompany()->getId())) {
                    return;
                }
            }

            throw new ValidationFail('[CancelReservation] Tenant cannot cancel reservations outside their company');
        }

        if ($user instanceof Employee) {
            if (
                !$user->getCompany()
                || !$user->getCompanyAddress()
                || !$user->getCompany()->getId()->equals($service->getCompany()->getId())
                || !$user->getCompanyAddress()->getId()->equals($service->getCompanyAddress()->getId())
            ) {
                throw new ValidationFail('[CancelReservation] Employee cannot cancel reservations outside their company location');
            }

            return;
        }

        throw new ValidationFail('[CancelReservation] Guest reservations are not handled through authenticated cancel flow');
    }
}
