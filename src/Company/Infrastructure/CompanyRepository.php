<?php

declare(strict_types=1);

namespace App\Company\Infrastructure;

use App\Company\Domain\Entity\Company;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Uid\Uuid;

class CompanyRepository implements CompanyRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->repository = $this->entityManager->getRepository(Company::class);
    }

    public function save(Company $company): void
    {
        $this->entityManager->persist($company);
        $this->entityManager->flush();
    }

    public function findById(Uuid $id): ?Company
    {
        return $this->repository->findOneBy(['id' => $id]);
    }

    public function findByName(string $name): ?Company
    {
        return $this->repository->findOneBy(['displayName' => $name]);
    }
}
