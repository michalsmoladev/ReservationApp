<?php

declare(strict_types=1);

namespace App\Reservation\Application\CancelReservation;

use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CancelReservationHandler
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CancelReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->findById($command->reservationId);

        if (!$reservation) {
            throw new \RuntimeException('[CancelReservation] Reservation not found during cancellation');
        }

        $reservation->cancel();
        $this->reservationRepository->save($reservation);

        $this->logger->info('[CancelReservation] Reservation canceled', [
            'reservation_id' => $reservation->getId()->toString(),
        ]);
    }
}
