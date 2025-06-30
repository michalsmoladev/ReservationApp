<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUser;

use App\User\Application\Factory\UserFactory;
use App\User\Domain\Entity\UserInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateUserHandler
{
    public function __construct(
        private readonly UserInterface $userRepository,
        private readonly UserFactory $userFactory,
    ) {
    }

    public function __invoke(CreateUserCommand $command): void
    {
        $user = $this->userFactory->create($command->userDTO, $command->uuid);

        $this->userRepository->save($user);
    }
}