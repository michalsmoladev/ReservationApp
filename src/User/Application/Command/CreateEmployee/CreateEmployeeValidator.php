<?php

declare(strict_types=1);

namespace App\User\Application\Command\CreateEmployee;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;

#[AsMessageValidator]
readonly class CreateEmployeeValidator
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateEmployeeCommand $command): void
    {
        $employee = $this->employeeRepository->findByEmail($command->employeeDto->email);

        if ($employee) {
            $this->logger->error('[CreateEmployee] Employee already exists.', ['email' => $command->employeeDto->email]);

            throw new ValidationFail(sprintf('Employee with email %s already exists', $command->employeeDto->email));
        }
    }
}