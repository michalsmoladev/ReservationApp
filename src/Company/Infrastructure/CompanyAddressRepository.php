<?php

declare(strict_types=1);

namespace App\Company\Infrastructure;

use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
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
}
