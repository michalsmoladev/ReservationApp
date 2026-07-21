<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query\GetServiceAvailability;

use App\Reservation\Application\Exception\ServiceNotFoundException;
use App\Reservation\Application\Query\DTO\AvailabilitySlotDTO;
use App\Reservation\Application\Query\DTO\ServiceAvailabilityDTO;
use App\Reservation\Domain\Entity\CompanyOpeningHour;
use App\Reservation\Domain\Entity\CompanyOpeningHour\CompanyOpeningHourRepositoryInterface;
use App\Reservation\Domain\Entity\EmployeeAbsence;
use App\Reservation\Domain\Entity\EmployeeAbsence\EmployeeAbsenceRepositoryInterface;
use App\Reservation\Domain\Entity\EmployeeWorkingHour;
use App\Reservation\Domain\Entity\EmployeeWorkingHour\EmployeeWorkingHourRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetServiceAvailabilityHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly CompanyOpeningHourRepositoryInterface $companyOpeningHourRepository,
        private readonly EmployeeWorkingHourRepositoryInterface $employeeWorkingHourRepository,
        private readonly EmployeeAbsenceRepositoryInterface $employeeAbsenceRepository,
        private readonly \App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface $reservationRepository,
    ) {
    }

    public function __invoke(GetServiceAvailabilityQuery $query): ServiceAvailabilityDTO
    {
        $service = $this->serviceRepository->findById($query->serviceId);

        if (!$service) {
            throw new ServiceNotFoundException();
        }

        $employees = $service->getEmployees()->toArray();
        $companyOpeningHours = $this->companyOpeningHourRepository->findByCompanyAndDateRange(
            companyId: $service->getCompany()->getId(),
            from: $query->from,
            to: $query->to,
            companyAddressId: $service->getCompanyAddress()->getId(),
        );

        $employeeWorkingHours = [];
        $employeeAbsences = [];
        $employeeIds = [];

        foreach ($employees as $employee) {
            \assert($employee instanceof Employee);

            $employeeIds[] = $employee->getUuid();
            $employeeWorkingHours[$employee->getUuid()->toString()] = $this->employeeWorkingHourRepository->findByEmployeeAndDateRange(
                employeeId: $employee->getUuid(),
                from: $query->from,
                to: $query->to,
            );
            $employeeAbsences[$employee->getUuid()->toString()] = $this->employeeAbsenceRepository->findByEmployeeAndDateRange(
                employeeId: $employee->getUuid(),
                from: $query->from,
                to: $query->to,
            );
        }

        $employeeReservations = [];
        foreach ($this->reservationRepository->findActiveByEmployeesAndDateRange($employeeIds, $query->from, $query->to) as $reservation) {
            \assert($reservation instanceof Reservation);

            if (!$reservation->getEmployeeId()) {
                continue;
            }

            $employeeReservations[$reservation->getEmployeeId()->toString()][] = $reservation;
        }

        $slotMap = [];
        $durationSeconds = (int) round($service->getDuration() * 60);
        $cursor = $query->from->setTime(0, 0);
        $endDay = $query->to->setTime(0, 0);

        while ($cursor <= $endDay) {
            $dayOpeningHour = $this->resolveOpeningHourForDay($companyOpeningHours, $cursor);

            if (!$dayOpeningHour || $dayOpeningHour->isClosed()) {
                $cursor = $cursor->modify('+1 day');
                continue;
            }

            $companyInterval = $this->buildDayInterval(
                day: $cursor,
                startsAt: $dayOpeningHour->getOpensAt(),
                endsAt: $dayOpeningHour->getClosesAt(),
                lowerBound: $query->from,
                upperBound: $query->to,
            );

            if (null === $companyInterval) {
                $cursor = $cursor->modify('+1 day');
                continue;
            }

            foreach ($employees as $employee) {
                \assert($employee instanceof Employee);

                $workingHour = $this->resolveEmployeeWorkingHourForDay(
                    $employeeWorkingHours[$employee->getUuid()->toString()] ?? [],
                    $cursor,
                );

                if (!$workingHour) {
                    continue;
                }

                $workingInterval = $this->buildDayInterval(
                    day: $cursor,
                    startsAt: $workingHour->getStartsAt(),
                    endsAt: $workingHour->getEndsAt(),
                    lowerBound: $query->from,
                    upperBound: $query->to,
                );

                if (null === $workingInterval) {
                    continue;
                }

                $availableIntervals = $this->intersectIntervals($companyInterval, $workingInterval);

                if ([] === $availableIntervals) {
                    continue;
                }

                $busyIntervals = [
                    ...$this->buildAbsenceIntervals($employeeAbsences[$employee->getUuid()->toString()] ?? []),
                    ...$this->buildReservationIntervals($employeeReservations[$employee->getUuid()->toString()] ?? []),
                ];

                $freeIntervals = $this->subtractIntervals($availableIntervals, $busyIntervals);

                foreach ($freeIntervals as [$freeStart, $freeEnd]) {
                    $slotStart = $freeStart;

                    while ($slotStart->modify(sprintf('+%d seconds', $durationSeconds)) <= $freeEnd) {
                        $slotEnd = $slotStart->modify(sprintf('+%d seconds', $durationSeconds));
                        $slotKey = $slotStart->format(\DateTimeImmutable::ATOM) . '|' . $slotEnd->format(\DateTimeImmutable::ATOM);

                        if (!isset($slotMap[$slotKey])) {
                            $slotMap[$slotKey] = [
                                'startsAt' => $slotStart,
                                'endsAt' => $slotEnd,
                                'employeeIds' => [],
                            ];
                        }

                        $slotMap[$slotKey]['employeeIds'][$employee->getUuid()->toString()] = $employee->getUuid()->toString();
                        $slotStart = $slotStart->modify(sprintf('+%d seconds', $durationSeconds));
                    }
                }
            }

            $cursor = $cursor->modify('+1 day');
        }

        usort($slotMap, static fn (array $left, array $right) => $left['startsAt'] <=> $right['startsAt']);

        $slots = array_map(
            static fn (array $slot) => new AvailabilitySlotDTO(
                startsAt: $slot['startsAt']->format(\DateTimeImmutable::ATOM),
                endsAt: $slot['endsAt']->format(\DateTimeImmutable::ATOM),
                employeeIds: array_values($slot['employeeIds']),
            ),
            $slotMap,
        );

        return new ServiceAvailabilityDTO(
            serviceId: $service->getId()->toString(),
            from: $query->from->format(\DateTimeImmutable::ATOM),
            to: $query->to->format(\DateTimeImmutable::ATOM),
            slots: $slots,
        );
    }

    private function resolveOpeningHourForDay(array $openingHours, \DateTimeImmutable $day): ?CompanyOpeningHour
    {
        $global = null;
        $scoped = null;
        $weekday = (int) $day->format('N');

        foreach ($openingHours as $openingHour) {
            \assert($openingHour instanceof CompanyOpeningHour);

            if ($openingHour->getDayOfWeek() !== $weekday) {
                continue;
            }

            if ($openingHour->getCompanyAddress()) {
                $scoped = $openingHour;
                continue;
            }

            $global = $openingHour;
        }

        return $scoped ?? $global;
    }

    private function resolveEmployeeWorkingHourForDay(array $workingHours, \DateTimeImmutable $day): ?EmployeeWorkingHour
    {
        $weekday = (int) $day->format('N');

        foreach ($workingHours as $workingHour) {
            \assert($workingHour instanceof EmployeeWorkingHour);

            if ($workingHour->getDayOfWeek() === $weekday) {
                return $workingHour;
            }
        }

        return null;
    }

    /**
     * @return array{0:\DateTimeImmutable,1:\DateTimeImmutable}|null
     */
    private function buildDayInterval(
        \DateTimeImmutable $day,
        ?\DateTimeImmutable $startsAt,
        ?\DateTimeImmutable $endsAt,
        \DateTimeImmutable $lowerBound,
        \DateTimeImmutable $upperBound,
    ): ?array {
        if (null === $startsAt || null === $endsAt) {
            return null;
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

        if ($intervalStart < $lowerBound) {
            $intervalStart = $lowerBound;
        }

        if ($intervalEnd > $upperBound) {
            $intervalEnd = $upperBound;
        }

        if ($intervalStart >= $intervalEnd) {
            return null;
        }

        return [$intervalStart, $intervalEnd];
    }

    /**
     * @param array{0:\DateTimeImmutable,1:\DateTimeImmutable} $left
     * @param array{0:\DateTimeImmutable,1:\DateTimeImmutable} $right
     * @return array<int, array{0:\DateTimeImmutable,1:\DateTimeImmutable}>
     */
    private function intersectIntervals(array $left, array $right): array
    {
        $start = $left[0] > $right[0] ? $left[0] : $right[0];
        $end = $left[1] < $right[1] ? $left[1] : $right[1];

        if ($start >= $end) {
            return [];
        }

        return [[$start, $end]];
    }

    /**
     * @param EmployeeAbsence[] $absences
     * @return array<int, array{0:\DateTimeImmutable,1:\DateTimeImmutable}>
     */
    private function buildAbsenceIntervals(array $absences): array
    {
        return array_map(
            static fn (EmployeeAbsence $absence) => [$absence->getStartsAt(), $absence->getEndsAt()],
            $absences,
        );
    }

    /**
     * @param Reservation[] $reservations
     * @return array<int, array{0:\DateTimeImmutable,1:\DateTimeImmutable}>
     */
    private function buildReservationIntervals(array $reservations): array
    {
        return array_map(
            static function (Reservation $reservation): array {
                $start = $reservation->getReservationDate();
                $end = $start->modify(sprintf('+%d seconds', (int) round($reservation->getServiceDuration() * 60)));

                return [$start, $end];
            },
            $reservations,
        );
    }

    /**
     * @param array<int, array{0:\DateTimeImmutable,1:\DateTimeImmutable}> $baseIntervals
     * @param array<int, array{0:\DateTimeImmutable,1:\DateTimeImmutable}> $busyIntervals
     * @return array<int, array{0:\DateTimeImmutable,1:\DateTimeImmutable}>
     */
    private function subtractIntervals(array $baseIntervals, array $busyIntervals): array
    {
        usort($busyIntervals, static fn (array $left, array $right) => $left[0] <=> $right[0]);

        $result = $baseIntervals;

        foreach ($busyIntervals as [$busyStart, $busyEnd]) {
            $next = [];

            foreach ($result as [$freeStart, $freeEnd]) {
                if ($busyEnd <= $freeStart || $busyStart >= $freeEnd) {
                    $next[] = [$freeStart, $freeEnd];
                    continue;
                }

                if ($busyStart > $freeStart) {
                    $next[] = [$freeStart, $busyStart];
                }

                if ($busyEnd < $freeEnd) {
                    $next[] = [$busyEnd, $freeEnd];
                }
            }

            $result = $next;
        }

        return array_values(array_filter(
            $result,
            static fn (array $interval) => $interval[0] < $interval[1],
        ));
    }
}
