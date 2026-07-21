<?php

declare(strict_types=1);

namespace App\Reservation\Presentation\Controller;

use App\Reservation\Application\CreateService\CreateServiceCommand;
use App\Reservation\Application\CreateService\DTO\CreateServiceDTO;
use App\Reservation\Application\Query\GetServiceAvailability\GetServiceAvailabilityQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class ServiceController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route(path: '/api/service', name: 'app_api_service_create', methods: ['POST'])]
    public function createServiceAction(#[MapRequestPayload] CreateServiceDTO $createServiceDTO): JsonResponse
    {
        $id = Uuid::v7();

        $this->commandBus->dispatch(
            new CreateServiceCommand(
                createServiceDTO: $createServiceDTO,
                id: $id,
            )
        );

        return new JsonResponse(data: ['id' => $id->toString()], status: Response::HTTP_OK);
    }

    #[Route(path: '/api/service/{id}/availability', name: 'app_api_service_availability', methods: ['GET'])]
    public function getServiceAvailabilityAction(string $id, Request $request): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if (!\is_string($from) || !\is_string($to)) {
            return new JsonResponse(data: 'Missing from or to query parameter', status: Response::HTTP_BAD_REQUEST);
        }

        try {
            $fromDate = new \DateTimeImmutable($from);
            $toDate = new \DateTimeImmutable($to);
        } catch (\Exception) {
            return new JsonResponse(data: 'Invalid from or to datetime', status: Response::HTTP_BAD_REQUEST);
        }

        if ($fromDate >= $toDate) {
            return new JsonResponse(data: 'from must be earlier than to', status: Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(
            new GetServiceAvailabilityQuery(
                serviceId: Uuid::fromString($id),
                from: $fromDate,
                to: $toDate,
            )
        );

        return new JsonResponse(
            data: $envelope->last(HandledStamp::class)->getResult(),
            status: Response::HTTP_OK,
        );
    }
}
