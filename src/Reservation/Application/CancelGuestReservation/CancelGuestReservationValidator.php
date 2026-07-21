<?php

declare(strict_types=1);

namespace App\Reservation\Application\CancelGuestReservation;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation\ReservationStatusEnum;

#[AsMessageValidator]
class CancelGuestReservationValidator
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
    ) {
    }

    public function __invoke(CancelGuestReservationCommand $command): void
    {
        if ('' === trim($command->guestCancellationToken)) {
            throw new ValidationFail('[CancelGuestReservation] Guest cancellation token cannot be blank');
        }

        $reservation = $this->reservationRepository->findByGuestCancellationToken($command->guestCancellationToken);

        if (!$reservation) {
            throw new ValidationFail('[CancelGuestReservation] Reservation not found');
        }

        if ($reservation->getStatus() === ReservationStatusEnum::CANCELED->value) {
            throw new ValidationFail('[CancelGuestReservation] Reservation is already canceled');
        }

        if (null !== $reservation->getCustomerId()) {
            throw new ValidationFail('[CancelGuestReservation] Guest reservation was already claimed by a customer');
        }

        if (null === $reservation->getGuestEmail()) {
            throw new ValidationFail('[CancelGuestReservation] Reservation is not a guest reservation');
        }

        if ($reservation->getReservationDate() < new \DateTimeImmutable('+24 hours')) {
            throw new ValidationFail('[CancelGuestReservation] Guest reservations can be canceled at least 24 hours before the appointment');
        }
    }
}
