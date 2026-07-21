<?php

declare(strict_types=1);

namespace App\Reservation\Application\AcceptReservation;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation\ReservationStatusEnum;

#[AsMessageValidator]
class AcceptReservationValidator
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
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
    }
}
