<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUser;

use App\User\Domain\Entity\User;
use App\User\Infrastructure\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class UpdateUserHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        /** @var User $user */
        $user = $this->userRepository->findByUuid(uuid: Uuid::fromString($command->uuid));

        $user->update(properties: (array) $command);

        $this->logger->info('[UpdateUserHandler] User updated', ['userId' => $command->uuid]);
    }
}