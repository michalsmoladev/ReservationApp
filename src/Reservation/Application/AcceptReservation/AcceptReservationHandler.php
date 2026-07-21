<?php

declare(strict_types=1);

namespace App\Reservation\Application\AcceptReservation;

use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AcceptReservationHandler
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(AcceptReservationCommand $command): void
    {
        $reservation = $this->reservationRepository->findById($command->reservationId);

        if (!$reservation) {
            throw new \RuntimeException('[AcceptReservation] Reservation not found during acceptance');
        }

        $reservation->accept();
        $this->reservationRepository->save($reservation);

        $this->logger->info('[AcceptReservation] Reservation accepted', [
            'reservation_id' => $reservation->getId()->toString(),
        ]);
    }
}
