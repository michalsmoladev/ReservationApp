<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateEmployeeAbsence;

use App\Reservation\Domain\Entity\EmployeeAbsence;
use App\Reservation\Domain\Entity\EmployeeAbsence\EmployeeAbsenceRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateEmployeeAbsenceHandler
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly EmployeeAbsenceRepositoryInterface $employeeAbsenceRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateEmployeeAbsenceCommand $command): void
    {
        $dto = $command->createEmployeeAbsenceDTO;
        $employee = $this->employeeRepository->findById(Uuid::fromString($dto->employeeId));

        if (!$employee) {
            throw new \RuntimeException('[CreateEmployeeAbsence] Employee not found during absence creation');
        }

        $absence = new EmployeeAbsence(
            employee: $employee,
            startsAt: new \DateTimeImmutable($dto->startsAt),
            endsAt: new \DateTimeImmutable($dto->endsAt),
            reason: trim($dto->reason),
        );

        $this->employeeAbsenceRepository->save($absence);

        $this->logger->info('[CreateEmployeeAbsence] Employee absence created', [
            'employee_id' => $employee->getUuid()->toString(),
        ]);
    }
}
