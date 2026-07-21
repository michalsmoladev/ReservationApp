<?php

declare(strict_types=1);

namespace App\Company\Application\Query\GetCompanyById;

use App\Company\Application\Exception\CompanyNotFoundException;
use App\Company\Application\Query\DTO\CompanyDetailsDTO;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Company\Domain\Service\CompanyService;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetCompanyByIdHandler
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyService $companyService,
        private readonly Security $security,
    ) {
    }

    public function __invoke(GetCompanyByIdQuery $query): CompanyDetailsDTO
    {
        $company = $this->companyRepository->findById($query->companyId);

        if (!$company) {
            throw new CompanyNotFoundException();
        }

        $user = $this->security->getUser();

        if (!$user instanceof Tenant) {
            throw new CompanyNotFoundException();
        }

        foreach ($user->getCompanies() as $tenantCompany) {
            if ($tenantCompany->getId()->equals($company->getId())) {
                return $this->companyService->createDtoFromEntity($company);
            }
        }

        throw new CompanyNotFoundException();
    }
}
