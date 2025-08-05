<?php

declare(strict_types=1);

namespace App\User\Application\Query\GetEmployeeById;

use App\User\Application\Exception\EmployeeNotFoundException;
use App\User\Application\Query\DTO\EmployeeDTO;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use App\User\Domain\Service\EmployeeService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class GetEmployeeByIdHandler
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly EmployeeService $employeeService,
    ) {
    }

    public function __invoke(GetEmployeeByIdQuery $query): EmployeeDTO
    {
        $employee = $this->employeeRepository->findById(Uuid::fromString($query->employeeId));

        if (!$employee) {
            throw new EmployeeNotFoundException();
        }

        return $this->employeeService->createEmployeeDtoFromEmployee($employee);
    }
}