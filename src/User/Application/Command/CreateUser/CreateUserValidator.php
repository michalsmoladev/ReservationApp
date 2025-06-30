<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateUser;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\UserInterface;

#[AsMessageValidator]
readonly class CreateUserValidator
{
    public function __construct(
        private UserInterface $userRepository,
    ) {
    }

    public function __invoke(CreateUserCommand $command): void
    {
        $user = $this->userRepository->findByEmail(email: $command->userDTO->email);

        if ($user) {
            throw new ValidationFail(sprintf('User with email %s already exists', $command->userDTO->email));
        }
    }
}