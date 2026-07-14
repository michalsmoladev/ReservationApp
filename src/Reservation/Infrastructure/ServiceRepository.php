<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\Service;
use App\Reservation\Domain\Entity\ServiceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function save(Service $service): void
    {
        $this->entityManager->persist($service);
        $this->entityManager->flush();
    }
}
