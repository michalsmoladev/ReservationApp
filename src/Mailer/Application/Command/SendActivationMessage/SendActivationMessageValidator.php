<?php

declare(strict_types=1);

namespace App\Mailer\Application\Command\SendActivationMessage;

use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\UserInterface;
use Symfony\Component\Uid\Uuid;

class SendActivationMessageValidator
{
    public function __construct(
        private readonly UserInterface $userRepository,
    ) {
    }

    public function __invoke(SendActivationMessageCommand $command): void
    {
        $user = $this->userRepository->findByUuid(Uuid::fromString($command->userId));

        if (!$user) {
            throw new ValidationFail('[SendActivationMessage] User not found');
        }

        if ($user->isActive()) {
            throw new ValidationFail('[SendActivationMessage] User already active');
        }
    }
}