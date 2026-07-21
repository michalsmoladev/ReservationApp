<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateEmployeeAbsence;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\EmployeeAbsence\EmployeeAbsenceRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class CreateEmployeeAbsenceValidator
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly EmployeeAbsenceRepositoryInterface $employeeAbsenceRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(CreateEmployeeAbsenceCommand $command): void
    {
        $dto = $command->createEmployeeAbsenceDTO;

        if (!Uuid::isValid($dto->employeeId)) {
            throw new ValidationFail('[CreateEmployeeAbsence] Employee id must be a valid UUID');
        }

        if ('' === trim($dto->reason)) {
            throw new ValidationFail('[CreateEmployeeAbsence] Reason cannot be blank');
        }

        $employee = $this->employeeRepository->findById(Uuid::fromString($dto->employeeId));

        if (!$employee) {
            throw new ValidationFail('[CreateEmployeeAbsence] Employee not found');
        }

        $this->assertOwnership($employee);

        try {
            $startsAt = new \DateTimeImmutable($dto->startsAt);
            $endsAt = new \DateTimeImmutable($dto->endsAt);
        } catch (\Exception) {
            throw new ValidationFail('[CreateEmployeeAbsence] startsAt and endsAt must be valid datetimes');
        }

        if ($startsAt >= $endsAt) {
            throw new ValidationFail('[CreateEmployeeAbsence] startsAt must be earlier than endsAt');
        }

        if ($this->employeeAbsenceRepository->hasOverlap($employee->getUuid(), $startsAt, $endsAt)) {
            throw new ValidationFail('[CreateEmployeeAbsence] Employee absence overlaps an existing absence');
        }
    }

    private function assertOwnership(Employee $employee): void
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[CreateEmployeeAbsence] Authenticated user is required');
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

            throw new ValidationFail('[CreateEmployeeAbsence] Tenant cannot manage another company employee calendar');
        }

        if ($user instanceof Employee) {
            if (!$user->getUuid()->equals($employee->getUuid())) {
                throw new ValidationFail('[CreateEmployeeAbsence] Employee can manage only their own absences');
            }

            return;
        }

        throw new ValidationFail('[CreateEmployeeAbsence] Only tenant or employee can manage employee absences');
    }
}
