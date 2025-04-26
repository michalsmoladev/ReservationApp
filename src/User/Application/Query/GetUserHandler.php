<?php

declare(strict_types=1);

namespace App\User\Application\Query;

use App\User\Application\Query\DTO\UserDTO;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\UserService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class GetUserHandler
{
    public function __construct(
        private UserInterface $userRepository,
        private UserService $userService,
    ) {
    }

    public function __invoke(GetUserQuery $query): UserDTO
    {
        /** @var User $user */
        $user = $this->userRepository->findByUuid(uuid: Uuid::fromString($query->uuid));

        return $this->userService->makeDtoFromEntity(user: $user);
    }
}