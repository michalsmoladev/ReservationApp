<?php

declare(strict_types=1);

namespace App\User\Application\Command\RemoveEmployee;

use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class RemoveEmployeeHandler
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(RemoveEmployeeCommand $command): void
    {
        $employee = $this->employeeRepository->findById(Uuid::fromString($command->employeeId));

        $this->employeeRepository->remove($employee);

        $this->logger->info('[RemoveEmployee] Employee removed successfully');
    }
}