<?php

declare(strict_types=1);

namespace App\Reservation\Application\CancelReservation;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation\ReservationStatusEnum;

#[AsMessageValidator]
class CancelReservationValidator
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
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
    }
}
