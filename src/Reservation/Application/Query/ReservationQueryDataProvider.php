<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query;

use App\Reservation\Application\Query\DTO\ReservationDetailsDTO;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Service;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Symfony\Component\Uid\Uuid;

class ReservationQueryDataProvider
{
    public function __construct(
        private readonly ServiceRepositoryInterface $serviceRepository,
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly CustomerRepositoryInterface $customerRepository,
    ) {
    }

    /**
     * @param Reservation[] $reservations
     * @return array<string, Service>
     */
    public function loadServicesByReservation(array $reservations): array
    {
        $serviceIds = [];

        foreach ($reservations as $reservation) {
            $serviceIds[$reservation->getServiceId()->toString()] = $reservation->getServiceId();
        }

        $services = [];

        foreach ($this->serviceRepository->findByIds(array_values($serviceIds)) as $service) {
            $services[$service->getId()->toString()] = $service;
        }

        return $services;
    }

    /**
     * @param Reservation[] $reservations
     * @return array<string, Employee>
     */
    public function loadEmployeesByReservation(array $reservations): array
    {
        $employeeIds = [];

        foreach ($reservations as $reservation) {
            if (null === $reservation->getEmployeeId()) {
                continue;
            }

            $employeeIds[$reservation->getEmployeeId()->toString()] = $reservation->getEmployeeId();
        }

        $employees = [];

        foreach ($this->employeeRepository->findByIds(array_values($employeeIds)) as $employee) {
            $employees[$employee->getUuid()->toString()] = $employee;
        }

        return $employees;
    }

    /**
     * @param Reservation[] $reservations
     * @return array<string, Customer>
     */
    public function loadCustomersByReservation(array $reservations): array
    {
        $customerIds = [];

        foreach ($reservations as $reservation) {
            if (null === $reservation->getCustomerId()) {
                continue;
            }

            $customerIds[$reservation->getCustomerId()->toString()] = $reservation->getCustomerId();
        }

        $customers = [];

        foreach ($this->customerRepository->findByIds(array_values($customerIds)) as $customer) {
            $customers[$customer->getUuid()->toString()] = $customer;
        }

        return $customers;
    }

    /**
     * @param array<string, Service> $services
     * @param array<string, Employee> $employees
     * @param array<string, Customer> $customers
     */
    public function mapReservationToDto(
        Reservation $reservation,
        array $services,
        array $employees,
        array $customers,
    ): ReservationDetailsDTO {
        $service = $services[$reservation->getServiceId()->toString()] ?? null;
        $employee = null !== $reservation->getEmployeeId()
            ? ($employees[$reservation->getEmployeeId()->toString()] ?? null)
            : null;
        $customer = null !== $reservation->getCustomerId()
            ? ($customers[$reservation->getCustomerId()->toString()] ?? null)
            : null;

        return new ReservationDetailsDTO(
            id: $reservation->getId()->toString(),
            reservationDate: $reservation->getReservationDate()->format(\DateTimeImmutable::ATOM),
            status: $reservation->getStatus(),
            serviceId: $reservation->getServiceId()->toString(),
            serviceName: $service?->getName(),
            companyId: $service?->getCompany()->getId()->toString(),
            companyName: $service?->getCompany()->getDisplayName(),
            companyAddressId: $service?->getCompanyAddress()->getId()->toString(),
            employeeId: $reservation->getEmployeeId()?->toString(),
            employeeFirstname: $employee?->getFirstname(),
            employeeLastname: $employee?->getLastname(),
            customerId: $reservation->getCustomerId()?->toString(),
            customerFirstname: $customer?->getFirstname(),
            customerLastname: $customer?->getLastname(),
            guestFirstname: $reservation->getGuestFirstname(),
            guestLastname: $reservation->getGuestLastname(),
            guestEmail: $reservation->getGuestEmail(),
            guestPhone: $reservation->getGuestPhone(),
            servicePrice: $reservation->getServicePrice(),
            serviceDuration: $reservation->getServiceDuration(),
            note: $reservation->getNote(),
            createdAt: $reservation->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            updatedAt: $reservation->getUpdatedAt()?->format(\DateTimeImmutable::ATOM),
        );
    }
}
