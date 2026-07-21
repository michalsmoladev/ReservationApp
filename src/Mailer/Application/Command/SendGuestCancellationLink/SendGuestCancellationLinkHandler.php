<?php

declare(strict_types=1);

namespace App\Mailer\Application\Command\SendGuestCancellationLink;

use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class SendGuestCancellationLinkHandler
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(SendGuestCancellationLinkCommand $command): void
    {
        $reservation = $this->reservationRepository->findById(Uuid::fromString($command->reservationId));

        if (!$reservation) {
            throw new \RuntimeException('[SendGuestCancellationLink] Reservation not found during message sending');
        }

        if (null === $reservation->getGuestEmail() || null === $reservation->getGuestCancellationToken()) {
            throw new \RuntimeException('[SendGuestCancellationLink] Reservation is not a guest reservation');
        }

        $this->logger->info('[SendGuestCancellationLink] Prepared guest cancellation link delivery', [
            'reservation_id' => $reservation->getId()->toString(),
            'guest_email' => $reservation->getGuestEmail(),
            'guest_cancellation_token' => $reservation->getGuestCancellationToken(),
            'guest_cancellation_path' => sprintf('/api/reservation/guest/cancel/%s', $reservation->getGuestCancellationToken()),
        ]);
    }
}
