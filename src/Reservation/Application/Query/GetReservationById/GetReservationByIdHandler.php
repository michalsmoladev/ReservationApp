<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetReservationById;

use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Application\Exception\ReservationNotFoundException;
use App\Reservation\Application\Query\DTO\ReservationDetailsDTO;
use App\Reservation\Application\Query\ReservationQueryDataProvider;
use App\Reservation\Application\Query\ReservationReadAccessChecker;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetReservationByIdHandler
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ReservationQueryDataProvider $reservationQueryDataProvider,
        private readonly ReservationReadAccessChecker $reservationReadAccessChecker,
        private readonly Security $security,
    ) {
    }

    public function __invoke(GetReservationByIdQuery $query): ReservationDetailsDTO
    {
        $reservation = $this->reservationRepository->findById($query->reservationId);

        if (!$reservation) {
            throw new ReservationNotFoundException();
        }

        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[GetReservationById] Authenticated user is required');
        }

        $services = $this->reservationQueryDataProvider->loadServicesByReservation([$reservation]);
        $service = $services[$reservation->getServiceId()->toString()] ?? null;

        if (!$this->reservationReadAccessChecker->canViewReservation($user, $reservation, $service)) {
            throw new ReservationNotFoundException();
        }

        $employees = $this->reservationQueryDataProvider->loadEmployeesByReservation([$reservation]);
        $customers = $this->reservationQueryDataProvider->loadCustomersByReservation([$reservation]);

        return $this->reservationQueryDataProvider->mapReservationToDto($reservation, $services, $employees, $customers);
    }
}
