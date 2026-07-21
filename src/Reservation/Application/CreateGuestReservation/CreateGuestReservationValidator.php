<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateGuestReservation;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Service;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class CreateGuestReservationValidator
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
    ) {
    }

    public function __invoke(CreateGuestReservationCommand $command): void
    {
        if (!Uuid::isValid($command->createGuestReservationDTO->serviceId)) {
            throw new ValidationFail('[CreateGuestReservation] Service id must be a valid UUID');
        }

        if ($command->createGuestReservationDTO->employeeId && !Uuid::isValid($command->createGuestReservationDTO->employeeId)) {
            throw new ValidationFail('[CreateGuestReservation] Employee id must be a valid UUID');
        }

        foreach ([
            'firstname' => $command->createGuestReservationDTO->firstname,
            'lastname' => $command->createGuestReservationDTO->lastname,
            'email' => $command->createGuestReservationDTO->email,
            'phone' => $command->createGuestReservationDTO->phone,
        ] as $field => $value) {
            if ('' === trim($value)) {
                throw new ValidationFail(sprintf('[CreateGuestReservation] %s cannot be blank', ucfirst($field)));
            }
        }

        if (!filter_var($command->createGuestReservationDTO->email, FILTER_VALIDATE_EMAIL)) {
            throw new ValidationFail('[CreateGuestReservation] Email must be valid');
        }

        if ($command->createGuestReservationDTO->note && \strlen($command->createGuestReservationDTO->note) > 255) {
            throw new ValidationFail('[CreateGuestReservation] Note cannot be longer than 255 characters');
        }

        try {
            $reservationDate = new \DateTimeImmutable($command->createGuestReservationDTO->reservationDate);
        } catch (\Exception) {
            throw new ValidationFail('[CreateGuestReservation] Reservation date must be a valid date');
        }

        if ($reservationDate <= new \DateTimeImmutable()) {
            throw new ValidationFail('[CreateGuestReservation] Reservation date must be in the future');
        }

        $service = $this->serviceRepository->findById(Uuid::fromString($command->createGuestReservationDTO->serviceId));

        if (!$service) {
            throw new ValidationFail('[CreateGuestReservation] Service not found');
        }

        if (!$command->createGuestReservationDTO->employeeId) {
            if (!$this->hasAvailableEmployee($service, $reservationDate)) {
                throw new ValidationFail('[CreateGuestReservation] No available employee for the selected service and date');
            }

            return;
        }

        $employee = $this->employeeRepository->findById(Uuid::fromString($command->createGuestReservationDTO->employeeId));

        if (!$employee) {
            throw new ValidationFail('[CreateGuestReservation] Employee not found');
        }

        if (!$service->getEmployees()->contains($employee)) {
            throw new ValidationFail('[CreateGuestReservation] Employee is not assigned to the selected service');
        }

        if ($this->reservationRepository->employeeHasReservationConflict(
            employeeId: $employee->getUuid(),
            reservationDate: $reservationDate,
            serviceDuration: $service->getDuration(),
        )) {
            throw new ValidationFail('[CreateGuestReservation] Employee already has a conflicting reservation');
        }
    }

    private function hasAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): bool
    {
        foreach ($service->getEmployees() as $employee) {
            if ($this->reservationRepository->employeeHasReservationConflict(
                employeeId: $employee->getUuid(),
                reservationDate: $reservationDate,
                serviceDuration: $service->getDuration(),
            )) {
                continue;
            }

            return true;
        }

        return false;
    }
}
