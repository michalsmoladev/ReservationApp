<?php

declare(strict_types=1);

namespace App\Company\Application\Command\CreateCompanyAddress;

use App\Company\Application\Factory\CompanyAddressFactory;
use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateCompanyAddressHandler
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly CompanyAddressFactory $companyAddressFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateCompanyAddressCommand $command): void
    {
        $company = $this->companyRepository->findById($command->companyId);

        if (!$company) {
            throw new \RuntimeException('[CreateCompanyAddress] Company not found during address creation');
        }

        $dto = new \App\Company\Application\Command\CreateCompany\DTO\CreateCompanyAddressDTO(
            street: trim($command->createCompanyAddressDTO->street),
            city: trim($command->createCompanyAddressDTO->city),
            apartmentNo: $command->createCompanyAddressDTO->apartmentNo,
            buildingNo: $command->createCompanyAddressDTO->buildingNo,
            postCode: trim($command->createCompanyAddressDTO->postalCode),
            country: trim($command->createCompanyAddressDTO->country),
            name: null !== $command->createCompanyAddressDTO->name ? trim($command->createCompanyAddressDTO->name) : null,
        );

        $address = $this->companyAddressFactory->create($dto);
        $address->setId($command->addressId);
        $company->addAddress($address);
        $this->companyAddressRepository->save($address);

        $this->logger->info('[CreateCompanyAddress] Company address created', [
            'company_id' => $company->getId()->toString(),
            'company_address_id' => $address->getId()->toString(),
        ]);
    }
}
