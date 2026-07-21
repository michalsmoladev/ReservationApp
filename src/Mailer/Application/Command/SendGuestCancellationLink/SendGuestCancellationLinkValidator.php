<?php

declare(strict_types=1);

namespace App\Mailer\Application\Command\SendGuestCancellationLink;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class SendGuestCancellationLinkValidator
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
    ) {
    }

    public function __invoke(SendGuestCancellationLinkCommand $command): void
    {
        if (!Uuid::isValid($command->reservationId)) {
            throw new ValidationFail('[SendGuestCancellationLink] Reservation id must be a valid UUID');
        }

        $reservation = $this->reservationRepository->findById(Uuid::fromString($command->reservationId));

        if (!$reservation) {
            throw new ValidationFail('[SendGuestCancellationLink] Reservation not found');
        }

        if (null === $reservation->getGuestEmail() || null === $reservation->getGuestCancellationToken()) {
            throw new ValidationFail('[SendGuestCancellationLink] Reservation is not a guest reservation');
        }
    }
}
