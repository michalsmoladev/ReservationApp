<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateReservation;

use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class CreateReservationValidator
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
    ) {
    }

    public function __invoke(CreateReservationCommand $command): void
    {
        if (!Uuid::isValid($command->createReservationDTO->serviceId)) {
            throw new ValidationFail('[CreateReservation] Service id must be a valid UUID');
        }

        if (!Uuid::isValid($command->createReservationDTO->customerId)) {
            throw new ValidationFail('[CreateReservation] Customer id must be a valid UUID');
        }

        if ($command->createReservationDTO->employeeId && !Uuid::isValid($command->createReservationDTO->employeeId)) {
            throw new ValidationFail('[CreateReservation] Employee id must be a valid UUID');
        }

        if ($command->createReservationDTO->note && \strlen($command->createReservationDTO->note) > 255) {
            throw new ValidationFail('[CreateReservation] Note cannot be longer than 255 characters');
        }

        try {
            $reservationDate = new \DateTimeImmutable($command->createReservationDTO->reservationDate);
        } catch (\Exception) {
            throw new ValidationFail('[CreateReservation] Reservation date must be a valid date');
        }

        if ($reservationDate <= new \DateTimeImmutable()) {
            throw new ValidationFail('[CreateReservation] Reservation date must be in the future');
        }

        $service = $this->serviceRepository->findById(Uuid::fromString($command->createReservationDTO->serviceId));

        if (!$service) {
            throw new ValidationFail('[CreateReservation] Service not found');
        }

        $customer = $this->customerRepository->findById(Uuid::fromString($command->createReservationDTO->customerId));

        if (!$customer) {
            throw new ValidationFail('[CreateReservation] Customer not found');
        }

        if (!$command->createReservationDTO->employeeId) {
            return;
        }

        $employee = $this->employeeRepository->findById(Uuid::fromString($command->createReservationDTO->employeeId));

        if (!$employee) {
            throw new ValidationFail('[CreateReservation] Employee not found');
        }

        if (!$service->getEmployees()->contains($employee)) {
            throw new ValidationFail('[CreateReservation] Employee is not assigned to the selected service');
        }

        if ($this->reservationRepository->employeeHasReservationConflict(
            employeeId: $employee->getUuid(),
            reservationDate: $reservationDate,
            serviceDuration: $service->getDuration(),
        )) {
            throw new ValidationFail('[CreateReservation] Employee already has a conflicting reservation');
        }
    }
}
