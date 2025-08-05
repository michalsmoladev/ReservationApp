<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateEmployee;

use App\User\Application\Factory\EmployeeFactory;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
readonly class CreateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private EmployeeFactory $employeeFactory,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateEmployeeCommand $command): void
    {
        $employee = $this->employeeFactory->create($command->employeeDto, $command->uuid);

        $this->employeeRepository->save($employee);

        $this->logger->info('[CreateEmployee] Created employee', ['employee_id' => $employee->getUuid()->toString()]);
    }
}