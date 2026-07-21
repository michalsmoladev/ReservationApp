<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\Service;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class ServiceRepository implements ServiceRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(Service::class);
    }

    public function save(Service $service): void
    {
        $this->entityManager->persist($service);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?Service
    {
        return $this->repository->find($id);
    }

    public function findByFilters(
        ?Uuid $companyId,
        ?Uuid $companyAddressId,
        bool $onlyActive = true,
    ): array {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('s')
            ->from(Service::class, 's')
            ->orderBy('s.name', 'ASC')
        ;

        if (null !== $companyId) {
            $qb->andWhere('IDENTITY(s.company) = :companyId')
                ->setParameter('companyId', $companyId);
        }

        if (null !== $companyAddressId) {
            $qb->andWhere('IDENTITY(s.companyAddress) = :companyAddressId')
                ->setParameter('companyAddressId', $companyAddressId);
        }

        if ($onlyActive) {
            $qb->andWhere('s.isActive = :isActive')
                ->setParameter('isActive', true);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByIds(array $ids): array
    {
        return $this->repository->findBy(['id' => $ids]);
    }
}
