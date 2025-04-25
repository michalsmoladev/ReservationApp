<?php

declare(strict_types=1);

namespace App\User\Presentation\Controller;

use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Application\Command\CreateUser\DTO\CreateUserDto;
use App\User\Application\Command\UpdateUser\DTO\UpdateUserDto;
use App\User\Application\Command\UpdateUser\UpdateUserCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class UserController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    #[Route(path: '/api/user', name: 'app_api_user_create', methods: ['POST'])]
    public function createAction(
        #[MapRequestPayload] CreateUserDto $createUserDto
    ): JsonResponse {
        $uuid = Uuid::v7();

        $command = new CreateUserCommand(
            email: $createUserDto->email,
            password: $createUserDto->password,
        );
        $command->uuid = (string) $uuid;

        $this->commandBus->dispatch($command);

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/user/{uuid}', name: 'app_api_user_update', methods: ['PATCH'])]
    public function updateAction(
        string $uuid,
        #[MapRequestPayload] UpdateUserDto $updateUserDto,
    ): JsonResponse {
        if (!Uuid::isValid($uuid)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new UpdateUserCommand(
                uuid: $uuid,
                email: $updateUserDto->email,
                password: $updateUserDto->password,
                roles: $updateUserDto->roles,
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }
}