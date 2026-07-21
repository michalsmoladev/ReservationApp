<?php

declare(strict_types=1);

namespace App\Reservation\Presentation\Controller;

use App\Reservation\Application\AcceptReservation\AcceptReservationCommand;
use App\Reservation\Application\CancelGuestReservation\CancelGuestReservationCommand;
use App\Reservation\Application\CancelReservation\CancelReservationCommand;
use App\Reservation\Application\CreateReservation\CreateReservationCommand;
use App\Reservation\Application\CreateReservation\DTO\CreateReservationDTO;
use App\Reservation\Application\CreateGuestReservation\CreateGuestReservationCommand;
use App\Reservation\Application\CreateGuestReservation\DTO\CreateGuestReservationDTO;
use App\Reservation\Application\Query\GetReservationById\GetReservationByIdQuery;
use App\Reservation\Application\Query\GetReservations\GetReservationsQuery;
use App\Reservation\Domain\Entity\Reservation\ReservationStatusEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class ReservationController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
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

    #[Route(path: '/api/reservation/guest', name: 'app_api_reservation_guest_create', methods: ['POST'])]
    public function createGuestReservationAction(#[MapRequestPayload] CreateGuestReservationDTO $createGuestReservationDTO): JsonResponse
    {
        $id = Uuid::v7();
        $guestCancellationToken = Uuid::v7()->toString();

        $this->commandBus->dispatch(
            new CreateGuestReservationCommand(
                createGuestReservationDTO: $createGuestReservationDTO,
                id: $id,
                guestCancellationToken: $guestCancellationToken,
            )
        );

        return new JsonResponse(
            data: [
                'id' => $id->toString(),
                'guestCancellationToken' => $guestCancellationToken,
            ],
            status: Response::HTTP_OK,
        );
    }

    #[Route(path: '/api/reservation/{id}/accept', name: 'app_api_reservation_accept', methods: ['POST'])]
    public function acceptReservationAction(string $id): JsonResponse
    {
        $this->commandBus->dispatch(
            new AcceptReservationCommand(
                reservationId: Uuid::fromString($id),
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/reservation/{id}/cancel', name: 'app_api_reservation_cancel', methods: ['POST'])]
    public function cancelReservationAction(string $id): JsonResponse
    {
        $this->commandBus->dispatch(
            new CancelReservationCommand(
                reservationId: Uuid::fromString($id),
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/reservation/guest/cancel/{token}', name: 'app_api_reservation_guest_cancel', methods: ['POST'])]
    public function cancelGuestReservationAction(string $token): JsonResponse
    {
        if ('' === trim($token)) {
            return new JsonResponse(data: 'Invalid token', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new CancelGuestReservationCommand(
                guestCancellationToken: $token,
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/reservation/{id}', name: 'app_api_reservation_show', methods: ['GET'])]
    public function showReservationAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(
            new GetReservationByIdQuery(reservationId: Uuid::fromString($id))
        );

        return new JsonResponse(
            data: $envelope->last(HandledStamp::class)->getResult(),
            status: Response::HTTP_OK,
        );
    }

    #[Route(path: '/api/reservations', name: 'app_api_reservation_list', methods: ['GET'])]
    public function listReservationsAction(Request $request): JsonResponse
    {
        $companyId = $request->query->get('companyId');
        $employeeId = $request->query->get('employeeId');
        $customerId = $request->query->get('customerId');
        $status = $request->query->get('status');

        foreach ([
            'companyId' => $companyId,
            'employeeId' => $employeeId,
            'customerId' => $customerId,
        ] as $field => $value) {
            if (null !== $value && !Uuid::isValid($value)) {
                return new JsonResponse(data: sprintf('Invalid %s', $field), status: Response::HTTP_BAD_REQUEST);
            }
        }

        if (null !== $status && null === ReservationStatusEnum::tryFrom($status)) {
            return new JsonResponse(data: 'Invalid status', status: Response::HTTP_BAD_REQUEST);
        }

        try {
            $from = null !== $request->query->get('from')
                ? new \DateTimeImmutable($request->query->get('from'))
                : null;
            $to = null !== $request->query->get('to')
                ? new \DateTimeImmutable($request->query->get('to'))
                : null;
        } catch (\Exception) {
            return new JsonResponse(data: 'Invalid date range', status: Response::HTTP_BAD_REQUEST);
        }

        if (null !== $from && null !== $to && $from > $to) {
            return new JsonResponse(data: 'Invalid date range', status: Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(
            new GetReservationsQuery(
                companyId: null !== $companyId ? Uuid::fromString($companyId) : null,
                employeeId: null !== $employeeId ? Uuid::fromString($employeeId) : null,
                customerId: null !== $customerId ? Uuid::fromString($customerId) : null,
                from: $from,
                to: $to,
                status: $status,
            )
        );

        return new JsonResponse(
            data: $envelope->last(HandledStamp::class)->getResult(),
            status: Response::HTTP_OK,
        );
    }
}
