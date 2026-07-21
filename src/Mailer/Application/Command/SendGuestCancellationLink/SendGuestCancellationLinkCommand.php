<?php

declare(strict_types=1);

namespace App\Mailer\Application\Command\SendGuestCancellationLink;

class SendGuestCancellationLinkCommand
{
    public function __construct(
        public readonly string $reservationId,
    ) {
    }
}
