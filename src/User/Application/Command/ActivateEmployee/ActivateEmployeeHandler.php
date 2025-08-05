<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateEmployee;

use App\User\Domain\Entity\Employee\Employee;
use App\User\Infrastructure\EmployeeRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ActivateEmployeeHandler
{
    public function __construct(
        private readonly EmployeeRepository $employeeRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ActivateEmployeeCommand $command): void
    {
        /** @var Employee $employee */
        $employee = $this->employeeRepository->findByToken($command->token);

        $employee->markAsActive();

        $this->employeeRepository->save($employee);

        $this->logger->info(
            '[ActivateEmployee] Employee was activated',
            [
                'employee_id' => $employee->getUuid()->toString(),
            ],
        );
    }
}