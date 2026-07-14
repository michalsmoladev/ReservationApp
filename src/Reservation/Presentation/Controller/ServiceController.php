<?php

declare(strict_types=1);

namespace App\Reservation\Presentation\Controller;

use App\Reservation\Application\CreateService\CreateServiceCommand;
use App\Reservation\Application\CreateService\DTO\CreateServiceDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class ServiceController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    #[Route(path: '/api/service', name: 'app_api_service_create', methods: ['POST'])]
    public function createServiceAction(#[MapRequestPayload] CreateServiceDTO $createServiceDTO): JsonResponse
    {
        $id = Uuid::v7();
        $command = new CreateServiceCommand(serviceDTO: $createServiceDTO);
        $command->id = $id;

        $this->commandBus->dispatch($command);

        return new JsonResponse(data: ['id' => $id->toString()], status: Response::HTTP_CREATED);
    }
}
