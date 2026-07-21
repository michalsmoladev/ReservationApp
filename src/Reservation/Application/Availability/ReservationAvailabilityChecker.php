<?php

declare(strict_types=1);

namespace App\Reservation\Application\Availability;

use App\Reservation\Domain\Entity\CompanyOpeningHour;
use App\Reservation\Domain\Entity\CompanyOpeningHour\CompanyOpeningHourRepositoryInterface;
use App\Reservation\Domain\Entity\EmployeeWorkingHour;
use App\Reservation\Domain\Entity\EmployeeAbsence\EmployeeAbsenceRepositoryInterface;
use App\Reservation\Domain\Entity\EmployeeWorkingHour\EmployeeWorkingHourRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Service;
use App\User\Domain\Entity\Employee\Employee;

class ReservationAvailabilityChecker
{
    public function __construct(
        private readonly CompanyOpeningHourRepositoryInterface $companyOpeningHourRepository,
        private readonly EmployeeWorkingHourRepositoryInterface $employeeWorkingHourRepository,
        private readonly EmployeeAbsenceRepositoryInterface $employeeAbsenceRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
    ) {
    }

    public function hasAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): bool
    {
        return null !== $this->findAvailableEmployee($service, $reservationDate);
    }

    public function findAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): ?Employee
    {
        foreach ($service->getEmployees() as $employee) {
            \assert($employee instanceof Employee);

            if ($this->isEmployeeAvailableForService($service, $employee, $reservationDate)) {
                return $employee;
            }
        }

        return null;
    }

    public function isEmployeeAvailableForService(
        Service $service,
        Employee $employee,
        \DateTimeImmutable $reservationDate,
    ): bool {
        $reservationEnd = $this->calculateReservationEnd($reservationDate, $service->getDuration());
        $openingHour = $this->resolveOpeningHourForDate($service, $reservationDate, $reservationEnd);

        if (!$openingHour || $openingHour->isClosed()) {
            return false;
        }

        if (!$this->isWithinDayInterval(
            day: $reservationDate,
            startsAt: $openingHour->getOpensAt(),
            endsAt: $openingHour->getClosesAt(),
            reservationStart: $reservationDate,
            reservationEnd: $reservationEnd,
        )) {
            return false;
        }

        $workingHour = $this->resolveEmployeeWorkingHourForDate($employee, $reservationDate, $reservationEnd);

        if (!$workingHour) {
            return false;
        }

        if (!$this->isWithinDayInterval(
            day: $reservationDate,
            startsAt: $workingHour->getStartsAt(),
            endsAt: $workingHour->getEndsAt(),
            reservationStart: $reservationDate,
            reservationEnd: $reservationEnd,
        )) {
            return false;
        }

        if ($this->employeeAbsenceRepository->hasOverlap($employee->getUuid(), $reservationDate, $reservationEnd)) {
            return false;
        }

        if ($this->reservationRepository->employeeHasReservationConflict(
            employeeId: $employee->getUuid(),
            reservationDate: $reservationDate,
            serviceDuration: $service->getDuration(),
        )) {
            return false;
        }

        return true;
    }

    private function resolveOpeningHourForDate(
        Service $service,
        \DateTimeImmutable $reservationDate,
        \DateTimeImmutable $reservationEnd,
    ): ?CompanyOpeningHour {
        $openingHours = $this->companyOpeningHourRepository->findByCompanyAndDateRange(
            companyId: $service->getCompany()->getId(),
            from: $reservationDate,
            to: $reservationEnd,
            companyAddressId: $service->getCompanyAddress()->getId(),
        );

        $global = null;
        $scoped = null;
        $weekday = (int) $reservationDate->format('N');

        foreach ($openingHours as $openingHour) {
            \assert($openingHour instanceof CompanyOpeningHour);

            if ($openingHour->getDayOfWeek() !== $weekday) {
                continue;
            }

            if (null !== $openingHour->getCompanyAddress()) {
                $scoped = $openingHour;
                continue;
            }

            $global = $openingHour;
        }

        return $scoped ?? $global;
    }

    private function resolveEmployeeWorkingHourForDate(
        Employee $employee,
        \DateTimeImmutable $reservationDate,
        \DateTimeImmutable $reservationEnd,
    ): ?EmployeeWorkingHour {
        $workingHours = $this->employeeWorkingHourRepository->findByEmployeeAndDateRange(
            employeeId: $employee->getUuid(),
            from: $reservationDate,
            to: $reservationEnd,
        );
        $weekday = (int) $reservationDate->format('N');

        foreach ($workingHours as $workingHour) {
            \assert($workingHour instanceof EmployeeWorkingHour);

            if ($workingHour->getDayOfWeek() === $weekday) {
                return $workingHour;
            }
        }

        return null;
    }

    private function isWithinDayInterval(
        \DateTimeImmutable $day,
        ?\DateTimeImmutable $startsAt,
        ?\DateTimeImmutable $endsAt,
        \DateTimeImmutable $reservationStart,
        \DateTimeImmutable $reservationEnd,
    ): bool {
        if (null === $startsAt || null === $endsAt) {
            return false;
        }

        $intervalStart = $day->setTime(
            (int) $startsAt->format('H'),
            (int) $startsAt->format('i'),
            (int) $startsAt->format('s'),
        );
        $intervalEnd = $day->setTime(
            (int) $endsAt->format('H'),
            (int) $endsAt->format('i'),
            (int) $endsAt->format('s'),
        );

        return $reservationStart >= $intervalStart && $reservationEnd <= $intervalEnd;
    }

    private function calculateReservationEnd(
        \DateTimeImmutable $reservationDate,
        float $serviceDuration,
    ): \DateTimeImmutable {
        $durationInSeconds = (int) round($serviceDuration * 60);

        return $reservationDate->modify(sprintf('+%d seconds', $durationInSeconds));
    }
}
