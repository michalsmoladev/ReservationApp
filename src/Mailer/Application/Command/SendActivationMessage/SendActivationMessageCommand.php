<?php

declare(strict_types=1);

namespace App\Mailer\Application\Command\SendActivationMessage;

class SendActivationMessageCommand
{
    public function __construct(
        public readonly string $userId,
    ) {
    }
}