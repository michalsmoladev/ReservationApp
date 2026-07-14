<?php

declare(strict_types=1);

namespace App\Reservation\Application\Factory;

use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Company;
use App\Reservation\Application\CreateService\DTO\CreateServiceDTO;
use App\Reservation\Domain\Entity\Service;
use App\User\Domain\Entity\Employee\Employee;
use Symfony\Component\Uid\Uuid;

class ServiceFactory
{
    public function create(CreateServiceDTO $serviceDTO, Uuid $id, Company $company, CompanyAddress $companyAddress, array $employees): Service
    {
        $service = new Service(
            name: $serviceDTO->name,
            description: $serviceDTO->description,
            duration: $serviceDTO->duration,
            price: $serviceDTO->price,
            company: $company,
            companyAddress: $companyAddress,
        );

        $service->setId($id);

        foreach ($employees as $employee) {
            if ($employee instanceof Employee) {
                $service->addEmployee($employee);
            }
        }

        return $service;
    }
}
