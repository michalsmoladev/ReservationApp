<?php

declare(strict_types=1);

namespace App\Company\Application\Query\GetCompanies;

use App\Company\Application\Query\DTO\CompanyCollectionDTO;
use App\Company\Domain\Service\CompanyService;
use App\User\Domain\Entity\Tenant\Tenant;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
class GetCompaniesHandler
{
    public function __construct(
        private readonly CompanyService $companyService,
        private readonly Security $security,
    ) {
    }

    public function __invoke(GetCompaniesQuery $query): CompanyCollectionDTO
    {
        $user = $this->security->getUser();

        if (!$user instanceof Tenant) {
            return new CompanyCollectionDTO(companies: []);
        }

        $items = [];

        foreach ($user->getCompanies() as $company) {
            if (!$company->isActive()) {
                continue;
            }

            $items[] = $this->companyService->createDtoFromEntity($company);
        }

        return new CompanyCollectionDTO(companies: $items);
    }
}
