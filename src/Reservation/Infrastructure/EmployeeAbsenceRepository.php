<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\EmployeeAbsence;
use App\Reservation\Domain\Entity\EmployeeAbsence\EmployeeAbsenceRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class EmployeeAbsenceRepository implements EmployeeAbsenceRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(EmployeeAbsence::class);
    }

    public function save(EmployeeAbsence $employeeAbsence): void
    {
        $this->entityManager->persist($employeeAbsence);
        $this->entityManager->flush();
    }

    public function hasOverlap(
        Uuid $employeeId,
        \DateTimeImmutable $startsAt,
        \DateTimeImmutable $endsAt,
    ): bool {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(ea.id)')
            ->from(EmployeeAbsence::class, 'ea')
            ->where('IDENTITY(ea.employee) = :employeeId')
            ->andWhere('ea.startsAt < :endsAt')
            ->andWhere('ea.endsAt > :startsAt')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('startsAt', $startsAt)
            ->setParameter('endsAt', $endsAt)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    public function findByEmployeeAndDateRange(
        Uuid $employeeId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        return $this->entityManager->createQueryBuilder()
            ->select('ea')
            ->from(EmployeeAbsence::class, 'ea')
            ->where('IDENTITY(ea.employee) = :employeeId')
            ->andWhere('ea.startsAt < :to')
            ->andWhere('ea.endsAt > :from')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('ea.startsAt', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
