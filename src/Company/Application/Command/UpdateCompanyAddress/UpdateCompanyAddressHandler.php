<?php

declare(strict_types=1);

namespace App\Company\Application\Command\UpdateCompanyAddress;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateCompanyAddressHandler
{
    public function __construct(
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(UpdateCompanyAddressCommand $command): void
    {
        $address = $this->companyAddressRepository->findById($command->companyAddressId);

        if (!$address) {
            throw new \RuntimeException('[UpdateCompanyAddress] Company address not found during update');
        }

        $address->update(
            street: trim($command->updateCompanyAddressDTO->street),
            city: trim($command->updateCompanyAddressDTO->city),
            country: trim($command->updateCompanyAddressDTO->country),
            postalCode: trim($command->updateCompanyAddressDTO->postalCode),
            apartmentNo: $command->updateCompanyAddressDTO->apartmentNo,
            buildingNo: $command->updateCompanyAddressDTO->buildingNo,
            name: null !== $command->updateCompanyAddressDTO->name ? trim($command->updateCompanyAddressDTO->name) : null,
        );

        $this->companyAddressRepository->save($address);

        $this->logger->info('[UpdateCompanyAddress] Company address updated', [
            'company_address_id' => $address->getId()->toString(),
        ]);
    }
}
