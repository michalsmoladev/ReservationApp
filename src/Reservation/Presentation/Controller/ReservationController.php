<?php

declare(strict_types=1);

namespace App\Reservation\Presentation\Controller;

use App\Reservation\Application\CreateReservation\CreateReservationCommand;
use App\Reservation\Application\CreateReservation\DTO\CreateReservationDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class ReservationController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    #[Route(path: '/api/reservation', name: 'app_api_reservation_create', methods: ['POST'])]
    public function createReservationAction(#[MapRequestPayload] CreateReservationDTO $createReservationDTO): JsonResponse
    {
        $id = Uuid::v7();

        $this->commandBus->dispatch(
            new CreateReservationCommand(
                createReservationDTO: $createReservationDTO,
                id: $id,
            )
        );

        return new JsonResponse(data: ['id' => $id->toString()], status: Response::HTTP_OK);
    }
}
