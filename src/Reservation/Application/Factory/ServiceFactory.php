<?php

declare(strict_types=1);

namespace App\Reservation\Application\Factory;

use App\Reservation\Application\CreateService\DTO\CreateServiceDTO;
use App\Reservation\Domain\Entity\Service;
use Symfony\Component\Uid\Uuid;

class ServiceFactory
{
    public function create(CreateServiceDTO $serviceDTO, Uuid $id): Service
    {
        $service = new Service(
            name: $serviceDTO->name,
            description: $serviceDTO->description,
            duration: $serviceDTO->duration,
            price: $serviceDTO->price,
        );

        $service->setId($id);

        return $service;
    }
}
