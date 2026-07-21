<?php

declare(strict_types=1);

namespace App\Reservation\Application\AcceptReservation;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation\ReservationStatusEnum;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

#[AsMessageValidator]
class AcceptReservationValidator
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(AcceptReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->findById($command->reservationId);

        if (!$reservation) {
            throw new ValidationFail('[AcceptReservation] Reservation not found');
        }

        if ($reservation->getStatus() !== ReservationStatusEnum::WAITING_FOR_APPROVAL->value) {
            throw new ValidationFail('[AcceptReservation] Only reservations waiting for approval can be accepted');
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[AcceptReservation] Authenticated user is required');
        }

        $service = $this->serviceRepository->findById($reservation->getServiceId());

        if (!$service) {
            throw new ValidationFail('[AcceptReservation] Service not found for reservation');
        }

        if ($user instanceof Tenant) {
            foreach ($user->getCompanies() as $company) {
                if ($company->getId()->equals($service->getCompany()->getId())) {
                    return;
                }
            }

            throw new ValidationFail('[AcceptReservation] Tenant cannot accept reservations outside their company');
        }

        if ($user instanceof Employee) {
            if (
                !$user->getCompany()
                || !$user->getCompanyAddress()
                || !$user->getCompany()->getId()->equals($service->getCompany()->getId())
                || !$user->getCompanyAddress()->getId()->equals($service->getCompanyAddress()->getId())
            ) {
                throw new ValidationFail('[AcceptReservation] Employee cannot accept reservations outside their company location');
            }

            return;
        }

        throw new ValidationFail('[AcceptReservation] Only tenant or employee can accept reservations');
    }
}
