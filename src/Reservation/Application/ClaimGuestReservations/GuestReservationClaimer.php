<?php

declare(strict_types=1);

namespace App\Reservation\Application\ClaimGuestReservations;

use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\User\Domain\Entity\Customer\Customer;
use Psr\Log\LoggerInterface;

class GuestReservationClaimer
{
    public function __construct(
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function claimForCustomer(Customer $customer): void
    {
        $claimedReservations = $this->reservationRepository->claimGuestReservationsByEmail(
            customerId: $customer->getUuid(),
            email: $customer->getEmail(),
        );

        if ($claimedReservations <= 0) {
            return;
        }

        $this->logger->info(
            '[GuestReservationClaimer] Claimed guest reservations for customer',
            [
                'customer_id' => $customer->getUuid()->toString(),
                'claimed_count' => $claimedReservations,
            ],
        );
    }
}
