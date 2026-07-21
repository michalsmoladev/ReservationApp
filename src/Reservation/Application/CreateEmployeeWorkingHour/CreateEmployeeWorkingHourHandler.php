<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateEmployeeWorkingHour;

use App\Reservation\Domain\Entity\EmployeeWorkingHour;
use App\Reservation\Domain\Entity\EmployeeWorkingHour\EmployeeWorkingHourRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateEmployeeWorkingHourHandler
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly EmployeeWorkingHourRepositoryInterface $employeeWorkingHourRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateEmployeeWorkingHourCommand $command): void
    {
        $dto = $command->createEmployeeWorkingHourDTO;
        $employee = $this->employeeRepository->findById(Uuid::fromString($dto->employeeId));

        if (!$employee) {
            throw new \RuntimeException('[CreateEmployeeWorkingHour] Employee not found during working hour creation');
        }

        $workingHour = new EmployeeWorkingHour(
            employee: $employee,
            dayOfWeek: $dto->dayOfWeek,
            startsAt: $this->parseTime($dto->startsAt),
            endsAt: $this->parseTime($dto->endsAt),
        );

        $this->employeeWorkingHourRepository->save($workingHour);

        $this->logger->info('[CreateEmployeeWorkingHour] Employee working hour created', [
            'employee_id' => $employee->getUuid()->toString(),
            'day_of_week' => $dto->dayOfWeek,
        ]);
    }

    private function parseTime(string $value): \DateTimeImmutable
    {
        $time = \DateTimeImmutable::createFromFormat('H:i', $value)
            ?: \DateTimeImmutable::createFromFormat('H:i:s', $value);

        if (false === $time) {
            throw new \RuntimeException('[CreateEmployeeWorkingHour] Invalid time during working hour creation');
        }

        return $time;
    }
}
