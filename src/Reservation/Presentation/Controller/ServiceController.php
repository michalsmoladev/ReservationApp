<?php

declare(strict_types=1);

namespace App\Reservation\Presentation\Controller;

use App\Reservation\Application\CreateService\CreateServiceCommand;
use App\Reservation\Application\CreateService\DTO\CreateServiceDTO;
use App\Reservation\Application\DeactivateService\DeactivateServiceCommand;
use App\Reservation\Application\Query\GetServiceById\GetServiceByIdQuery;
use App\Reservation\Application\Query\GetServices\GetServicesQuery;
use App\Reservation\Application\Query\GetServiceAvailability\GetServiceAvailabilityQuery;
use App\Reservation\Application\UpdateService\DTO\UpdateServiceDTO;
use App\Reservation\Application\UpdateService\UpdateServiceCommand;
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

    #[Route(path: '/api/service/{id}', name: 'app_api_service_show', methods: ['GET'])]
    public function showServiceAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(
            new GetServiceByIdQuery(
                serviceId: Uuid::fromString($id),
            )
        );

        return new JsonResponse(
            data: $envelope->last(HandledStamp::class)->getResult(),
            status: Response::HTTP_OK,
        );
    }

    #[Route(path: '/api/services', name: 'app_api_service_list', methods: ['GET'])]
    public function listServicesAction(Request $request): JsonResponse
    {
        $companyId = $request->query->get('companyId');
        $companyAddressId = $request->query->get('companyAddressId');

        foreach ([
            'companyId' => $companyId,
            'companyAddressId' => $companyAddressId,
        ] as $field => $value) {
            if (null !== $value && !Uuid::isValid($value)) {
                return new JsonResponse(data: sprintf('Invalid %s', $field), status: Response::HTTP_BAD_REQUEST);
            }
        }

        $envelope = $this->queryBus->dispatch(
            new GetServicesQuery(
                companyId: null !== $companyId ? Uuid::fromString($companyId) : null,
                companyAddressId: null !== $companyAddressId ? Uuid::fromString($companyAddressId) : null,
            )
        );

        return new JsonResponse(
            data: $envelope->last(HandledStamp::class)->getResult(),
            status: Response::HTTP_OK,
        );
    }

    #[Route(path: '/api/service/{id}', name: 'app_api_service_update', methods: ['PATCH'])]
    public function updateServiceAction(string $id, #[MapRequestPayload] UpdateServiceDTO $updateServiceDTO): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new UpdateServiceCommand(
                serviceId: Uuid::fromString($id),
                updateServiceDTO: $updateServiceDTO,
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/service/{id}', name: 'app_api_service_delete', methods: ['DELETE'])]
    public function deactivateServiceAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new DeactivateServiceCommand(
                serviceId: Uuid::fromString($id),
            )
        );

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
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
