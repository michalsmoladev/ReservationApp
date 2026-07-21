<?php

declare(strict_types=1);

namespace App\Reservation\Presentation\Controller;

use App\Reservation\Application\CreateCompanyOpeningHour\CreateCompanyOpeningHourCommand;
use App\Reservation\Application\CreateCompanyOpeningHour\DTO\CreateCompanyOpeningHourDTO;
use App\Reservation\Application\CreateEmployeeAbsence\CreateEmployeeAbsenceCommand;
use App\Reservation\Application\CreateEmployeeAbsence\DTO\CreateEmployeeAbsenceDTO;
use App\Reservation\Application\CreateEmployeeWorkingHour\CreateEmployeeWorkingHourCommand;
use App\Reservation\Application\CreateEmployeeWorkingHour\DTO\CreateEmployeeWorkingHourDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

class AvailabilityCalendarController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    #[Route(path: '/api/company-opening-hour', name: 'app_api_company_opening_hour_create', methods: ['POST'])]
    public function createCompanyOpeningHourAction(
        #[MapRequestPayload] CreateCompanyOpeningHourDTO $createCompanyOpeningHourDTO,
    ): JsonResponse {
        $this->commandBus->dispatch(new CreateCompanyOpeningHourCommand($createCompanyOpeningHourDTO));

        return new JsonResponse(status: Response::HTTP_CREATED);
    }

    #[Route(path: '/api/employee-working-hour', name: 'app_api_employee_working_hour_create', methods: ['POST'])]
    public function createEmployeeWorkingHourAction(
        #[MapRequestPayload] CreateEmployeeWorkingHourDTO $createEmployeeWorkingHourDTO,
    ): JsonResponse {
        $this->commandBus->dispatch(new CreateEmployeeWorkingHourCommand($createEmployeeWorkingHourDTO));

        return new JsonResponse(status: Response::HTTP_CREATED);
    }

    #[Route(path: '/api/employee-absence', name: 'app_api_employee_absence_create', methods: ['POST'])]
    public function createEmployeeAbsenceAction(
        #[MapRequestPayload] CreateEmployeeAbsenceDTO $createEmployeeAbsenceDTO,
    ): JsonResponse {
        $this->commandBus->dispatch(new CreateEmployeeAbsenceCommand($createEmployeeAbsenceDTO));

        return new JsonResponse(status: Response::HTTP_CREATED);
    }
}
