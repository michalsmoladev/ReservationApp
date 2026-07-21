<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class ReservationRepository implements ReservationRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(Reservation::class);
    }

    public function save(Reservation $reservation): void
    {
        $this->entityManager->persist($reservation);
        $this->entityManager->flush();
    }
}
