<?php

declare(strict_types=1);

namespace App\Reservation\Application\Query;

use App\Reservation\Application\Query\DTO\ServiceDetailsDTO;
use App\Reservation\Domain\Entity\Service;

class ServiceQueryDataProvider
{
    public function mapServiceToDto(Service $service): ServiceDetailsDTO
    {
        $employeeIds = [];

        foreach ($service->getEmployees() as $employee) {
            $employeeIds[] = $employee->getUuid()->toString();
        }

        return new ServiceDetailsDTO(
            id: $service->getId()->toString(),
            name: $service->getName(),
            description: $service->getDescription(),
            duration: $service->getDuration(),
            price: $service->getPrice(),
            companyId: $service->getCompany()->getId()->toString(),
            companyAddressId: $service->getCompanyAddress()->getId()->toString(),
            employeeIds: $employeeIds,
            isActive: $service->isActive(),
            createdAt: $service->getCreatedAt()->format(\DateTimeImmutable::ATOM),
            updatedAt: $service->getUpdatedAt()?->format(\DateTimeImmutable::ATOM),
        );
    }
}
