<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateEmployeeWorkingHour;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\EmployeeWorkingHour\EmployeeWorkingHourRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class CreateEmployeeWorkingHourValidator
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly EmployeeWorkingHourRepositoryInterface $employeeWorkingHourRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(CreateEmployeeWorkingHourCommand $command): void
    {
        $dto = $command->createEmployeeWorkingHourDTO;

        if (!Uuid::isValid($dto->employeeId)) {
            throw new ValidationFail('[CreateEmployeeWorkingHour] Employee id must be a valid UUID');
        }

        if ($dto->dayOfWeek < 1 || $dto->dayOfWeek > 7) {
            throw new ValidationFail('[CreateEmployeeWorkingHour] dayOfWeek must be between 1 and 7');
        }

        $employee = $this->employeeRepository->findById(Uuid::fromString($dto->employeeId));

        if (!$employee) {
            throw new ValidationFail('[CreateEmployeeWorkingHour] Employee not found');
        }

        $this->assertOwnership($employee);

        if ($this->employeeWorkingHourRepository->existsForDay($employee->getUuid(), $dto->dayOfWeek)) {
            throw new ValidationFail('[CreateEmployeeWorkingHour] Working hour for this employee and day already exists');
        }

        $startsAt = $this->parseTime($dto->startsAt, 'startsAt');
        $endsAt = $this->parseTime($dto->endsAt, 'endsAt');

        if ($startsAt >= $endsAt) {
            throw new ValidationFail('[CreateEmployeeWorkingHour] startsAt must be earlier than endsAt');
        }
    }

    private function assertOwnership(Employee $employee): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[CreateEmployeeWorkingHour] Authenticated user is required');
        }

        if ($user instanceof Tenant) {
            foreach ($user->getCompanies() as $tenantCompany) {
                if (
                    $employee->getCompany()
                    && $tenantCompany->getId()->equals($employee->getCompany()->getId())
                ) {
                    return;
                }
            }

            throw new ValidationFail('[CreateEmployeeWorkingHour] Tenant cannot manage another company employee calendar');
        }

        if ($user instanceof Employee) {
            if (!$user->getUuid()->equals($employee->getUuid())) {
                throw new ValidationFail('[CreateEmployeeWorkingHour] Employee can manage only their own working hours');
            }

            return;
        }

        throw new ValidationFail('[CreateEmployeeWorkingHour] Only tenant or employee can manage employee working hours');
    }

    private function parseTime(string $value, string $field): \DateTimeImmutable
    {
        $time = \DateTimeImmutable::createFromFormat('H:i', $value)
            ?: \DateTimeImmutable::createFromFormat('H:i:s', $value);

        if (false === $time) {
            throw new ValidationFail(sprintf('[CreateEmployeeWorkingHour] %s must be a valid time', $field));
        }

        return $time;
    }
}
