<?php

declare(strict_types=1);

namespace App\User\Presentation\Controller;

use App\User\Application\Command\CreateUser\CreateUserCommand;
use App\User\Application\Command\CreateUser\DTO\CreateUserDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

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
        $this->commandBus->dispatch(
            new CreateUserCommand(
                email: $createUserDto->email,
                password: $createUserDto->password,
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }
}