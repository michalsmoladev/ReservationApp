<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateUser;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\UserInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
readonly class UpdateUserValidator
{
    public function __construct(
        private UserInterface $userRepository,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->userRepository->findByUuid(uuid: Uuid::fromString($command->uuid));

        if (!$user) {
            throw new ValidationFail(sprintf('User not found with uuid: %s', $command->uuid));
        }
    }
}