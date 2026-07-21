<?php

declare(strict_types=1);

namespace App\Company\Application\Command\DeleteCompanyAddress;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class DeleteCompanyAddressHandler
{
    public function __construct(
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(DeleteCompanyAddressCommand $command): void
    {
        $address = $this->companyAddressRepository->findById($command->companyAddressId);

        if (!$address) {
            throw new \RuntimeException('[DeleteCompanyAddress] Company address not found during deletion');
        }

        $this->companyAddressRepository->remove($address);

        $this->logger->info('[DeleteCompanyAddress] Company address deleted', [
            'company_address_id' => $command->companyAddressId->toString(),
        ]);
    }
}
