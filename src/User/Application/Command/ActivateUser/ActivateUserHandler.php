<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateUser;

use App\User\Infrastructure\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ActivateUserHandler
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ActivateUserCommand $command): void
    {
        $user = $this->userRepository->findByToken($command->token);

        $user->markAsActive();

        $this->logger->info('Activated user: ' . (string) $user->getUuid());
    }
}
