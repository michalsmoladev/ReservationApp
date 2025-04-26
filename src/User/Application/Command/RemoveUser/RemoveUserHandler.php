<?php

declare(strict_types=1);

namespace App\User\Application\Command\RemoveUser;

use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class RemoveUserHandler
{
    public function __construct(
        private readonly UserInterface $userRepository,
    ) {
    }

    public function __invoke(RemoveUserCommand $command): void
    {
        /** @var User $user */
        $user = $this->userRepository->findByUuid(uuid: Uuid::fromString($command->uuid));

        $this->userRepository->remove($user);
    }
}