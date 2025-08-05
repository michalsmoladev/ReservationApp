<?php

declare(strict_types=1);

namespace App\User\Presentation\Controller;

use App\User\Application\Command\ActivateEmployee\ActivateEmployeeCommand;
use App\User\Application\Command\CreateEmployee\CreateEmployeeCommand;
use App\User\Application\Command\CreateEmployee\DTO\CreateEmployeeDto;
use App\User\Application\Command\RemoveEmployee\RemoveEmployeeCommand;
use App\User\Application\Command\UpdateEmployee\DTO\UpdateEmployeeDTO;
use App\User\Application\Command\UpdateEmployee\UpdateEmployeeCommand;
use App\User\Application\Query\GetEmployeeById\GetEmployeeByIdQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

class EmployeeController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
        private readonly MessageBusInterface $queryBus,
    ) {
    }

    #[Route(path: '/api/employee/create', name: 'app_api_employee_create', methods: ['POST'])]
    public function createEmployeeAction(#[MapRequestPayload] CreateEmployeeDto $createEmployeeDto): JsonResponse
    {
        $id = Uuid::v7();
        $command = new CreateEmployeeCommand(
            employeeDto: $createEmployeeDto
        );
        $command->uuid = $id->toString();

        $this->commandBus->dispatch($command);

        return new JsonResponse(data: ['id' => $id->toString()], status: Response::HTTP_OK);
    }

    #[Route(path: '/api/employee/{id}', name: 'app_api_employee_update', methods: ['PATCH'])]
    public function updateEmployeeAction(
        string $id,
        #[MapRequestPayload] UpdateEmployeeDTO $updateEmployeeDTO,
    ): JsonResponse {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new UpdateEmployeeCommand(
                uuid: $id,
                email: $updateEmployeeDTO->email,
                password: $updateEmployeeDTO->password,
                roles: $updateEmployeeDTO->roles,
                isActive: $updateEmployeeDTO->isActive,
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: '/api/employee/{id}', name: 'app_api_employee_show', methods: ['GET'])]
    public function showEmployeeByIdAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $envelop = $this->queryBus->dispatch(
            new GetEmployeeByIdQuery(
                employeeId: $id,
            )
        );

        $employeeDto = $envelop->last(HandledStamp::class)->getResult();

        return new JsonResponse(data: $employeeDto, status: Response::HTTP_OK);
    }

    #[Route(path: '/api/employee/{id}', name: 'app_api_employee_delete', methods: ['DELETE'])]
    public function deleteEmployeeByIdAction(string $id): JsonResponse
    {
        if (!Uuid::isValid($id)) {
            return new JsonResponse(data: 'Invalid uuid', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new RemoveEmployeeCommand(
                employeeId: $id,
            )
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }

    #[Route(path: 'api/employee/activate/{token}', name: 'app_api_employee_active', methods: ['GET'])]
    public function activeEmployeeAction(string $token): JsonResponse
    {
        if (!Uuid::isValid($token)) {
            return new JsonResponse(data: 'Invalid token', status: Response::HTTP_BAD_REQUEST);
        }

        $this->commandBus->dispatch(
            new ActivateEmployeeCommand(token: $token),
        );

        return new JsonResponse(status: Response::HTTP_OK);
    }
}