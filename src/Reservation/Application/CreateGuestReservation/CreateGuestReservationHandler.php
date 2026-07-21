<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateGuestReservation;

use App\Reservation\Application\Factory\ReservationFactory;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Service;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateGuestReservationHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ReservationFactory $reservationFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateGuestReservationCommand $command): void
    {
        $service = $this->serviceRepository->findById(Uuid::fromString($command->createGuestReservationDTO->serviceId));

        if (!$service) {
            throw new \RuntimeException('[CreateGuestReservation] Service not found during reservation creation');
        }

        $reservationDate = new \DateTimeImmutable($command->createGuestReservationDTO->reservationDate);
        $employee = $command->createGuestReservationDTO->employeeId
            ? $this->employeeRepository->findById(Uuid::fromString($command->createGuestReservationDTO->employeeId))
            : $this->findAvailableEmployee($service, $reservationDate);

        if (!$employee && !$command->createGuestReservationDTO->employeeId) {
            throw new \RuntimeException('[CreateGuestReservation] No available employee found during automatic assignment');
        }

        $reservation = $this->reservationFactory->createForGuest(
            reservationDTO: $command->createGuestReservationDTO,
            id: $command->id,
            service: $service,
            employee: $employee,
        );

        $this->reservationRepository->save($reservation);

        $this->logger->info('[CreateGuestReservation] Created guest reservation', ['reservation_id' => $reservation->getId()->toString()]);
    }

    private function findAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): ?Employee
    {
        foreach ($service->getEmployees() as $employee) {
            if ($this->reservationRepository->employeeHasReservationConflict(
                employeeId: $employee->getUuid(),
                reservationDate: $reservationDate,
                serviceDuration: $service->getDuration(),
            )) {
                continue;
            }

            return $employee;
        }

        return null;
    }
}
