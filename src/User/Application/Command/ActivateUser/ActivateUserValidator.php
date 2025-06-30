<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateUser;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\UserInterface;

#[AsMessageValidator]
class ActivateUserValidator
{
    public function __construct(
        private UserInterface $userRepository,
    ) {
    }

    public function __invoke(ActivateUserCommand $command): void
    {
        $user = $this->userRepository->findByToken($command->token);

        if (!$user) {
            throw new ValidationFail('User not found');
        }

        if ($user->getMetadata()->getActivationExpiresAt() < new \DateTimeImmutable()) {
            throw new ValidationFail('Token has expired');
        }
    }
}