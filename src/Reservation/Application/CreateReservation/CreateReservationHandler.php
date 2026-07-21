<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateReservation;

use App\Reservation\Application\Availability\ReservationAvailabilityChecker;
use App\Reservation\Application\Factory\ReservationFactory;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateReservationHandler
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly ReservationRepositoryInterface $reservationRepository,
        private readonly ReservationAvailabilityChecker $reservationAvailabilityChecker,
        private readonly ReservationFactory $reservationFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateReservationCommand $command): void
    {
        $service = $this->serviceRepository->findById(Uuid::fromString($command->createReservationDTO->serviceId));
        $customer = $this->customerRepository->findById(Uuid::fromString($command->createReservationDTO->customerId));

        if (!$service) {
            throw new \RuntimeException('[CreateReservation] Service not found during reservation creation');
        }

        if (!$service->isActive()) {
            throw new \RuntimeException('[CreateReservation] Service is not active during reservation creation');
        }

        if (!$customer) {
            throw new \RuntimeException('[CreateReservation] Customer not found during reservation creation');
        }

        $reservationDate = new \DateTimeImmutable($command->createReservationDTO->reservationDate);
        $employee = $command->createReservationDTO->employeeId
            ? $this->employeeRepository->findById(Uuid::fromString($command->createReservationDTO->employeeId))
            : $this->reservationAvailabilityChecker->findAvailableEmployee($service, $reservationDate);

        if (!$employee && $command->createReservationDTO->employeeId) {
            throw new \RuntimeException('[CreateReservation] Employee not found during reservation creation');
        }

        if (
            $employee
            && $command->createReservationDTO->employeeId
            && (
                !$service->getEmployees()->contains($employee)
                || !$this->reservationAvailabilityChecker->isEmployeeAvailableForService($service, $employee, $reservationDate)
            )
        ) {
            throw new \RuntimeException('[CreateReservation] Employee is not available for the selected service and date');
        }

        if (!$employee && !$command->createReservationDTO->employeeId) {
            throw new \RuntimeException('[CreateReservation] No available employee found during automatic assignment');
        }

        $reservation = $this->reservationFactory->createForCustomer(
            reservationDTO: $command->createReservationDTO,
            id: $command->id,
            service: $service,
            customer: $customer,
            employee: $employee,
        );

        $this->reservationRepository->save($reservation);

        $this->logger->info('[CreateReservation] Created reservation', ['reservation_id' => $reservation->getId()->toString()]);
    }
}
