<?php

declare(strict_types=1);

namespace App\User\Infrastructure;

use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class EmployeeRepository implements EmployeeRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(Employee::class);
    }

    public function findByEmail(string $email): ?Employee
    {
        return $this->repository->findOneBy(['email' => $email]);
    }

    public function findById(Uuid $uuid): ?Employee
    {
        return $this->repository->findOneBy(['uuid' => $uuid]);
    }

    public function save(Employee $employee): void
    {
        $this->entityManager->persist($employee);
        $this->entityManager->flush();
    }

    public function lock(Uuid $uuid): void
    {
        $this->repository->find($uuid, LockMode::PESSIMISTIC_WRITE);
    }

    public function remove(Employee $employee): void
    {
        $this->entityManager->remove($employee);
        $this->entityManager->flush();
    }

    public function findByToken(string $token): ?Employee
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(Employee::class, 'u')
            ->join('u.metadata', 'metadata')
            ->where('metadata.activationToken = :token')
            ->setParameter('token', $token)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }
}