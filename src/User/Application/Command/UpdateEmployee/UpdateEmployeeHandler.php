<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateEmployee;

use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
readonly class UpdateEmployeeHandler
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateEmployeeCommand $command): void
    {
        /** @var Employee $employee */
        $employee = $this->employeeRepository->findById(Uuid::fromString($command->uuid));

        $employee->update(properties: (array) $command);

        $this->employeeRepository->save($employee);

        $this->logger->info('[UpdateEmployee] Update employee', ['employee_id' => $employee->getUuid()->toString()]);
    }
}