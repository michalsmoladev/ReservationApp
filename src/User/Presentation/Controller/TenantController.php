<?php

declare(strict_types=1);

namespace App\User\Presentation\Controller;

use App\User\Application\Command\ActivateTenant\ActivateTenantCommand;
use App\User\Application\Command\CreateTenant\CreateTenantCommand;
use App\User\Application\Command\CreateTenant\DTO\CreateTenantDTO;
use App\User\Application\Command\RemoveTenant\RemoveTenantCommand;
use App\User\Application\Command\UpdateTenant\DTO\UpdateTenantDTO;
use App\User\Application\Command\UpdateTenant\UpdateTenantCommand;
use App\User\Application\Query\GetTenantById\GetTenantByIdQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class TenantController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route(path: '/api/tenant/create', name: 'app_api_tenant_create', methods: ['POST'])]
    public function createTenantAction(#[MapRequestPayload] CreateTenantDTO $createTenantDTO): JsonResponse
    {
        $id = Uuid::v7();

        $command = new CreateTenantCommand(tenantDTO: $createTenantDTO);
        $command->id = $id;

        $this->commandBus->dispatch($command);

        return new JsonResponse(data: ['id' => $id->toString()], status: Response::HTTP_CREATED);
    }

    #[Route(path: '/api/tenant/{id}', name: 'app_api_tenant_update', methods: ['PATCH'])]
    public function updateTenantAction(string $id, #[MapRequestPayload] UpdateTenantDTO $tenantDTO): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new UpdateTenantCommand(
                id: Uuid::fromString($id),
                tenantDTO: $tenantDTO
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/tenant/{id}', name: 'app_api_tenant_show', methods: ['GET'])]
    public function showTenantAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(
            new GetTenantByIdQuery(id: Uuid::fromString($id))
        );

        return new JsonResponse(
            data: $envelope->last(HandledStamp::class)->getResult(),
            status: Response::HTTP_OK
        );
    }

    #[Route(path: '/api/tenant/{id}', name: 'app_api_tenant_remove', methods: ['DELETE'])]
    public function removeTenantAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new RemoveTenantCommand(
                id: Uuid::fromString($id),
            )
        );

        return new JsonResponse(status: Response::HTTP_NO_CONTENT);
    }

    #[Route(path: '/api/tenant/activate/{token}', name: 'app_api_tenant_active', methods: ['GET'])]
    public function activeTenantAction(string $token): JsonResponse
    {
        if ('' === trim($token)) {
            return new JsonResponse(data: 'Invalid token', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new ActivateTenantCommand(token: $token),
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }
}
