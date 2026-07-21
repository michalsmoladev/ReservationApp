<?php

declare(strict_types=1);

namespace App\Reservation\Infrastructure;

use App\Reservation\Domain\Entity\CompanyOpeningHour;
use App\Reservation\Domain\Entity\CompanyOpeningHour\CompanyOpeningHourRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class CompanyOpeningHourRepository implements CompanyOpeningHourRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(CompanyOpeningHour::class);
    }

    public function save(CompanyOpeningHour $companyOpeningHour): void
    {
        $this->entityManager->persist($companyOpeningHour);
        $this->entityManager->flush();
    }

    public function findByCompanyAndDateRange(
        Uuid $companyId,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        ?Uuid $companyAddressId = null,
    ): array {
        $weekdays = $this->extractWeekdaysInRange($from, $to);

        $qb = $this->entityManager->createQueryBuilder()
            ->select('coh')
            ->from(CompanyOpeningHour::class, 'coh')
            ->where('IDENTITY(coh.company) = :companyId')
            ->andWhere('coh.dayOfWeek IN (:weekdays)')
            ->setParameter('companyId', $companyId)
            ->setParameter('weekdays', $weekdays)
            ->orderBy('coh.dayOfWeek', 'ASC')
        ;

        if (null === $companyAddressId) {
            $qb->andWhere('coh.companyAddress IS NULL');
        } else {
            $qb->andWhere('(coh.companyAddress IS NULL OR IDENTITY(coh.companyAddress) = :companyAddressId)')
                ->setParameter('companyAddressId', $companyAddressId);
        }

        return $qb->getQuery()->getResult();
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
