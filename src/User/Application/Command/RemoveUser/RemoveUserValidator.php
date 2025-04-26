<?php

declare(strict_types=1);

namespace App\User\Application\Command\RemoveUser;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\UserInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class RemoveUserValidator
{
    public function __construct(
        private UserInterface $userRepository,
    ) {
    }

    public function __invoke(RemoveUserCommand $command): void
    {
        $user = $this->userRepository->findByUuid(uuid: Uuid::fromString($command->uuid));

        if (!$user) {
            throw new ValidationFail('User not found');
        }
    }
}