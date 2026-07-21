<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\EmployeeWorkingHour;
use App\Reservation\Domain\Entity\EmployeeWorkingHour\EmployeeWorkingHourRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class EmployeeWorkingHourRepository implements EmployeeWorkingHourRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(EmployeeWorkingHour::class);
    }

    public function save(EmployeeWorkingHour $employeeWorkingHour): void
    {
        $this->entityManager->persist($employeeWorkingHour);
        $this->entityManager->flush();
    }

    public function existsForDay(Uuid $employeeId, int $dayOfWeek): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(ewh.id)')
            ->from(EmployeeWorkingHour::class, 'ewh')
            ->where('IDENTITY(ewh.employee) = :employeeId')
            ->andWhere('ewh.dayOfWeek = :dayOfWeek')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('dayOfWeek', $dayOfWeek)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    public function findByEmployeeAndDateRange(
        Uuid $employeeId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
    ): array {
        $weekdays = $this->extractWeekdaysInRange($from, $to);

        return $this->entityManager->createQueryBuilder()
            ->select('ewh')
            ->from(EmployeeWorkingHour::class, 'ewh')
            ->where('IDENTITY(ewh.employee) = :employeeId')
            ->andWhere('ewh.dayOfWeek IN (:weekdays)')
            ->setParameter('employeeId', $employeeId)
            ->setParameter('weekdays', $weekdays)
            ->orderBy('ewh.dayOfWeek', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return int[]
     */
    private function extractWeekdaysInRange(\DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $weekdays = [];
        $cursor = $from->setTime(0, 0);
        $end = $to->setTime(0, 0);

        while ($cursor <= $end) {
            $weekdays[(int) $cursor->format('N')] = (int) $cursor->format('N');
            $cursor = $cursor->modify('+1 day');
        }

        return array_values($weekdays);
    }
}
