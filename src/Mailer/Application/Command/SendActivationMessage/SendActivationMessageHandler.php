<?php

declare(strict_types=1);

namespace App\Mailer\Application\Command\SendActivationMessage;

use App\User\Domain\Entity\UserInterface;

class SendActivationMessageHandler
{
    public function __construct(
        private readonly UserInterface $userRepository,
    ) {
    }

    public function __invoke(SendActivationMessageCommand $command): void
    {
        
    }
}