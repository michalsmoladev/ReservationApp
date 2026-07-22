<?php

declare(strict_types=1);

namespace App\Company\Application\Query\GetCompanyAddresses;

use App\Company\Application\Exception\CompanyNotFoundException;
use App\Company\Application\Query\DTO\CompanyAddressCollectionDTO;
use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Company\Domain\Service\CompanyService;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetCompanyAddressesHandler
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly CompanyService $companyService,
        private readonly Security $security,
    ) {
    }

    public function __invoke(GetCompanyAddressesQuery $query): CompanyAddressCollectionDTO
    {
        $company = $this->companyRepository->findById($query->companyId);

        if (!$company || !$company->isActive()) {
            throw new CompanyNotFoundException();
        }

        $user = $this->security->getUser();

        if (!$user instanceof Tenant) {
            throw new CompanyNotFoundException();
        }

        foreach ($user->getCompanies() as $tenantCompany) {
            if ($tenantCompany->getId()->equals($company->getId())) {
                return new CompanyAddressCollectionDTO(
                    addresses: array_map(
                        fn ($address) => $this->companyService->createAddressDtoFromEntity($address),
                        $this->companyAddressRepository->findByCompanyId($company->getId()),
                    ),
                );
            }
        }

        throw new CompanyNotFoundException();
    }
}
