<?php

declare(strict_types=1);

namespace App\Company\Infrastructure;

use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Reservation\Domain\Entity\CompanyOpeningHour;
use App\Reservation\Domain\Entity\Service;
use App\User\Domain\Entity\Employee\Employee;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class CompanyAddressRepository implements CompanyAddressRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(CompanyAddress::class);
    }

    public function findById(Uuid $id): ?CompanyAddress
    {
        return $this->repository->find($id);
    }

    public function findByCompanyId(Uuid $companyId): array
    {
        return $this->repository->findBy(['company' => $companyId], ['name' => 'ASC', 'city' => 'ASC']);
    }

    public function save(CompanyAddress $companyAddress): void
    {
        $this->entityManager->persist($companyAddress);
        $this->entityManager->flush();
    }

    public function remove(CompanyAddress $companyAddress): void
    {
        $this->entityManager->remove($companyAddress);
        $this->entityManager->flush();
    }

    public function isUsed(Uuid $companyAddressId): bool
    {
        return $this->hasEmployeeAssignments($companyAddressId)
            || $this->hasServiceAssignments($companyAddressId)
            || $this->hasOpeningHourAssignments($companyAddressId);
    }

    private function hasEmployeeAssignments(Uuid $companyAddressId): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(e.uuid)')
            ->from(Employee::class, 'e')
            ->where('IDENTITY(e.companyAddress) = :companyAddressId')
            ->setParameter('companyAddressId', $companyAddressId)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    private function hasServiceAssignments(Uuid $companyAddressId): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(s.id)')
            ->from(Service::class, 's')
            ->where('IDENTITY(s.companyAddress) = :companyAddressId')
            ->setParameter('companyAddressId', $companyAddressId)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }

    private function hasOpeningHourAssignments(Uuid $companyAddressId): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(coh.id)')
            ->from(CompanyOpeningHour::class, 'coh')
            ->where('IDENTITY(coh.companyAddress) = :companyAddressId')
            ->setParameter('companyAddressId', $companyAddressId)
            ->getQuery()
            ->getSingleScalarResult() > 0
        ;
    }
}
