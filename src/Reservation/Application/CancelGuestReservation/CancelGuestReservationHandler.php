<?php

declare(strict_types=1);

namespace App\Reservation\Application\CancelGuestReservation;

use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CancelGuestReservationHandler
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CancelGuestReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->findByGuestCancellationToken($command->guestCancellationToken);

        if (!$reservation) {
            throw new \RuntimeException('[CancelGuestReservation] Reservation not found during cancellation');
        }

        $reservation->cancel();
        $this->reservationRepository->save($reservation);

        $this->logger->info('[CancelGuestReservation] Guest reservation canceled', [
            'reservation_id' => $reservation->getId()->toString(),
        ]);
    }
}
