<?php

declare(strict_types=1);

namespace App\User\Presentation\Controller;

use App\User\Application\Command\ActivateUser\ActivateUserCommand;
use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Application\Command\CreateUser\DTO\CreateUserDto;
use App\User\Application\Command\RemoveUser\RemoveUserCommand;
use App\User\Application\Command\UpdateUser\DTO\UpdateUserDto;
use App\User\Application\Command\UpdateUser\UpdateUserCommand;
use App\User\Application\Query\GetUserQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class UserController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route(path: '/api/user/create', name: 'app_api_user_create', methods: ['POST'])]
    public function createUserAction(
        #[MapRequestPayload] CreateUserDto $createUserDto
    ): JsonResponse {
        $uuid = Uuid::v7();

        $command = new CreateUserCommand($createUserDto);
        $command->uuid = (string) $uuid;

        $this->commandBus->dispatch($command);

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/user/{uuid}', name: 'app_api_user_update', methods: ['PATCH'])]
    public function updateUserAction(
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

    #[Route(path: '/api/user/{uuid}', name: 'app_api_user_delete', methods: ['DELETE'])]
    public function removeUserAction(string $uuid): JsonResponse
    {
        if (!Uuid::isValid($uuid)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new RemoveUserCommand(uuid: $uuid)
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/user/{uuid}', name: 'app_api_user_show', methods: ['GET'])]
    public function showUserAction(string $uuid): JsonResponse
    {
        if (!Uuid::isValid($uuid)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $envelop = $this->queryBus->dispatch(new GetUserQuery($uuid));
        $result = $envelop->last(HandledStamp::class)->getResult();

        return new JsonResponse(data: $result, status: Response::HTTP_OK);
    }

    #[Route(path: '/api/user/activate/{activationToken}', name: 'app_api_user_activate', methods: ['GET'])]
    public function activateAction(string $activationToken): JsonResponse
    {
        if (!Uuid::isValid($activationToken)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        try {
            $this->commandBus->dispatch(new ActivateUserCommand(token: $activationToken));
        } catch (\Exception $e) {
            return new JsonResponse(data: $e->getMessage(), status: Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse(status: Response::HTTP_OK);
    }
}