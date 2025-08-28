<?php

declare(strict_types=1);

namespace App\User\Presentation\Controller;

use App\User\Application\Command\CreateCustomer\CreateCustomerCommand;
use App\User\Application\Command\CreateCustomer\DTO\CreateCustomerDTO;
use App\User\Application\Command\RemoveCustomer\RemoveCustomerCommand;
use App\User\Application\Command\UpdateCustomer\DTO\UpdateCustomerDTO;
use App\User\Application\Command\UpdateCustomer\UpdateCustomerCommand;
use App\User\Application\Query\GetCustomerById\GetCustomerByIdQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class CustomerController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route(path: '/api/customer/create', name: 'app_api_customer_create', methods: ['POST'])]
    public function createEmployeeAction(#[MapRequestPayload] CreateCustomerDTO $createCustomerDTO): JsonResponse
    {
        $id = Uuid::v7();
        $command = new CreateCustomerCommand(
            createCustomerDTO: $createCustomerDTO
        );
        $command->id = $id;

        $this->commandBus->dispatch($command);

        return new JsonResponse(data: ['id' => $id->toString()], status: Response::HTTP_OK);
    }

    #[Route(path: '/api/customer/{id}', name: 'app_api_customer_update', methods: ['PATCH'])]
    public function updateEmployeeAction(
        string $id,
        #[MapRequestPayload] UpdateCustomerDTO $updateCustomerDTO,
    ): JsonResponse {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid ID', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new UpdateCustomerCommand(
                customerId: Uuid::fromString($id),
                dto: $updateCustomerDTO,
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/customer/{id}', name: 'app_api_customer_remove', methods: ['DELETE'])]
    public function removeEmployeeAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid ID', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new RemoveCustomerCommand(
                customerId: Uuid::fromString($id)
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/customer/{id}', name: 'app_api_customer_show', methods: ['GET'])]
    public function showCustomerAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid ID', status: Response::HTTP_BAD_REQUEST);
        }

        $envelope = $this->queryBus->dispatch(
            new GetCustomerByIdQuery(
                customerId: Uuid::fromString($id)
            )
        );

        return new JsonResponse(data: $envelope->last(HandledStamp::class)->getResult(), status: Response::HTTP_OK);
    }

    #[Route(path: 'api/customer/activate/{token}', name: 'app_api_customer_active', methods: ['GET'])]
    public function activeCustomerAction(string $token): JsonResponse
    {
        if (!Uuid::isValid($token)) {
            return new JsonResponse(data: 'Invalid ID', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new ActivateEmployeeCommand(token: $token),
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }
}