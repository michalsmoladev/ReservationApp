<?php

declare(strict_types=1);

namespace App\User\Application\Command\UpdateEmployee;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
readonly class UpdateEmployeeValidator
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateEmployeeCommand $command): void
    {
        $employee = $this->employeeRepository->findById(Uuid::fromString($command->uuid));

        if (!$employee) {
            $this->logger->error('[UpdateEmployee] Employee does not exists.', ['uuid' => $command->uuid]);

            throw new ValidationFail('Employee does not exists');
        }
    }
}