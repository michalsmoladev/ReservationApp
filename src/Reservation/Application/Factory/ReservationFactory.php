<?php

declare(strict_types=1);

namespace App\Reservation\Application\Factory;

use App\Reservation\Application\CreateReservation\DTO\CreateReservationDTO;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Service;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Employee\Employee;
use Symfony\Component\Uid\Uuid;

class ReservationFactory
{
    public function create(
        CreateReservationDTO $reservationDTO,
        Uuid $id,
        Service $service,
        Customer $customer,
        ?Employee $employee,
    ): Reservation {
        return Reservation::create(
            id: $id,
            reservationDate: new \DateTimeImmutable($reservationDTO->reservationDate),
            serviceId: $service->getId(),
            customerId: $customer->getUuid(),
            employeeId: $employee?->getUuid(),
            servicePrice: $service->getPrice(),
            serviceDuration: $service->getDuration(),
            note: $reservationDTO->note ? trim($reservationDTO->note) : null,
        );
    }
}
