<?php

declare(strict_types=1);

namespace App\User\Infrastructure;

use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class TenantRepository implements TenantRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(Tenant::class);
    }

    public function findByEmail(string $email): ?Tenant
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findById(Uuid $uuid): ?Tenant
    {
        return $this->repository->findOneBy(['uuid' => $uuid]);
    }

    public function save(Tenant $employee): void
    {
        $this->entityManager->persist($employee);
        $this->entityManager->flush();
    }

    public function lock(Uuid $uuid): void
    {
        $this->repository->find($uuid, LockMode::PESSIMISTIC_WRITE);
    }

    public function remove(Tenant $Employee): void
    {
        $this->entityManager->remove($Employee);
    }

    public function findByToken(string $token): ?Tenant
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(Tenant::class, 'u')
            ->join('u.metadata', 'metadata')
            ->where('metadata.activationToken = :token')
            ->setParameter('token', $token)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }
}
