<?php

declare(strict_types=1);

namespace App\Reservation\Application\Factory;

use App\Reservation\Application\CreateReservation\DTO\CreateReservationDTO;
use App\Reservation\Application\CreateGuestReservation\DTO\CreateGuestReservationDTO;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Service;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Employee\Employee;
use Symfony\Component\Uid\Uuid;

class ReservationFactory
{
    public function createForCustomer(
        CreateReservationDTO $reservationDTO,
        Uuid $id,
        Service $service,
        Customer $customer,
        ?Employee $employee,
    ): Reservation {
        return Reservation::createForCustomer(
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

    public function createForGuest(
        CreateGuestReservationDTO $reservationDTO,
        Uuid $id,
        Service $service,
        ?Employee $employee,
    ): Reservation {
        return Reservation::createForGuest(
            id: $id,
            reservationDate: new \DateTimeImmutable($reservationDTO->reservationDate),
            serviceId: $service->getId(),
            employeeId: $employee?->getUuid(),
            servicePrice: $service->getPrice(),
            serviceDuration: $service->getDuration(),
            guestFirstname: trim($reservationDTO->firstname),
            guestLastname: trim($reservationDTO->lastname),
            guestEmail: trim($reservationDTO->email),
            guestPhone: trim($reservationDTO->phone),
            note: $reservationDTO->note ? trim($reservationDTO->note) : null,
        );
    }
}
