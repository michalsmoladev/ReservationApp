<?php

declare(strict_types=1);

namespace App\User\Application\Command\RemoveEmployee;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class RemoveEmployeeValidator
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(RemoveEmployeeCommand $command): void
    {
        $employee = $this->employeeRepository->findById(Uuid::fromString($command->employeeId));

        if (!$employee) {
            $this->logger->info('[RemoveEmployee] Employee not found]', ['employeeId' => $command->employeeId]);

            throw new ValidationFail('Employee not found');
        }
    }
}