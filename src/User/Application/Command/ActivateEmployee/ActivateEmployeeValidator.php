<?php

declare(strict_types=1);

namespace App\User\Application\Command\ActivateEmployee;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;

#[AsMessageValidator]
class ActivateEmployeeValidator
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(ActivateEmployeeCommand $command): void
    {
        $employee = $this->employeeRepository->findByToken($command->token);

        if (!$employee) {
            $this->logger->info('[ActivateEmployee] Employee not found', ['token' => $command->token]);

            throw new ValidationFail('Employee not found');
        }

        if ($employee->isActive()) {
            $this->logger->info('[ActivateEmployee] Employee is already active', ['token' => $command->token]);
            throw new ValidationFail('Employee is already active');
        }

        if ($employee->getMetadata()->getActivationExpiresAt() < new \DateTimeImmutable()) {
            throw new ValidationFail('Token has expired');
        }
    }
}