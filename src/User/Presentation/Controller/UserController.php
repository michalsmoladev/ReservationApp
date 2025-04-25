<?php

namespace App\User\Presentation\Controller;

use App\User\Application\Command\CreateUser\DTO\CreateUserDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    public function __construct(

    ) {
    }

    #[Route(path: '/api/user', name: 'app_api_user_create', methods: ['POST'])]
    public function createAction(
        #[MapRequestPayload] CreateUserDto $createUserDto
    ): JsonResponse {

    }
}