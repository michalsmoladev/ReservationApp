<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateCompanyOpeningHour;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Reservation\Application\CreateCompanyOpeningHour\DTO\CreateCompanyOpeningHourDTO;
use App\Reservation\Domain\Entity\CompanyOpeningHour;
use App\Reservation\Domain\Entity\CompanyOpeningHour\CompanyOpeningHourRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
class CreateCompanyOpeningHourHandler
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly CompanyOpeningHourRepositoryInterface $companyOpeningHourRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CreateCompanyOpeningHourCommand $command): void
    {
        $dto = $command->createCompanyOpeningHourDTO;

        $company = $this->companyRepository->findById(Uuid::fromString($dto->companyId));

        if (!$company) {
            throw new \RuntimeException('[CreateCompanyOpeningHour] Company not found during opening hour creation');
        }

        $companyAddress = $dto->companyAddressId
            ? $this->companyAddressRepository->findById(Uuid::fromString($dto->companyAddressId))
            : null;

        $openingHour = new CompanyOpeningHour(
            company: $company,
            companyAddress: $companyAddress,
            dayOfWeek: $dto->dayOfWeek,
            opensAt: $this->parseTimeOrNull($dto, $dto->opensAt),
            closesAt: $this->parseTimeOrNull($dto, $dto->closesAt),
            isClosed: $dto->isClosed,
        );

        $this->companyOpeningHourRepository->save($openingHour);

        $this->logger->info('[CreateCompanyOpeningHour] Company opening hour created', [
            'company_id' => $company->getId()->toString(),
            'day_of_week' => $dto->dayOfWeek,
        ]);
    }

    private function parseTimeOrNull(CreateCompanyOpeningHourDTO $dto, ?string $value): ?\DateTimeImmutable
    {
        if ($dto->isClosed || null === $value) {
            return null;
        }

        $time = \DateTimeImmutable::createFromFormat('H:i', $value)
            ?: \DateTimeImmutable::createFromFormat('H:i:s', $value);

        if (false === $time) {
            throw new \RuntimeException('[CreateCompanyOpeningHour] Invalid time during opening hour creation');
        }

        return $time;
    }
}
