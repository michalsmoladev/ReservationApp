<?php

declare(strict_types=1);

namespace App\Reservation\Application\CreateCompanyOpeningHour;

use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Core\Application\MessageBus\Attribute\AsMessageValidator;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\CompanyOpeningHour\CompanyOpeningHourRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Uid\Uuid;

#[AsMessageValidator]
class CreateCompanyOpeningHourValidator
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly CompanyAddressRepositoryInterface $companyAddressRepository,
        private readonly CompanyOpeningHourRepositoryInterface $companyOpeningHourRepository,
        private readonly Security $security,
    ) {
    }

    public function __invoke(CreateCompanyOpeningHourCommand $command): void
    {
        $dto = $command->createCompanyOpeningHourDTO;

        if (!Uuid::isValid($dto->companyId)) {
            throw new ValidationFail('[CreateCompanyOpeningHour] Company id must be a valid UUID');
        }

        if ($dto->companyAddressId && !Uuid::isValid($dto->companyAddressId)) {
            throw new ValidationFail('[CreateCompanyOpeningHour] Company address id must be a valid UUID');
        }

        if ($dto->dayOfWeek < 1 || $dto->dayOfWeek > 7) {
            throw new ValidationFail('[CreateCompanyOpeningHour] dayOfWeek must be between 1 and 7');
        }

        $company = $this->companyRepository->findById(Uuid::fromString($dto->companyId));

        if (!$company || !$company->isActive()) {
            throw new ValidationFail('[CreateCompanyOpeningHour] Company not found');
        }

        $companyAddress = null;

        if ($dto->companyAddressId) {
            $companyAddress = $this->companyAddressRepository->findById(Uuid::fromString($dto->companyAddressId));

            if (!$companyAddress) {
                throw new ValidationFail('[CreateCompanyOpeningHour] Company address not found');
            }

            if (!$companyAddress->getCompany()?->getId()->equals($company->getId())) {
                throw new ValidationFail('[CreateCompanyOpeningHour] Company address does not belong to the given company');
            }
        }

        $this->assertOwnership($company, $companyAddress, $dto->companyAddressId !== null);

        if ($this->companyOpeningHourRepository->existsForDay(
            companyId: $company->getId(),
            dayOfWeek: $dto->dayOfWeek,
            companyAddressId: $companyAddress?->getId(),
        )) {
            throw new ValidationFail('[CreateCompanyOpeningHour] Opening hour for this company/location and day already exists');
        }

        if ($dto->isClosed) {
            if ($dto->opensAt !== null || $dto->closesAt !== null) {
                throw new ValidationFail('[CreateCompanyOpeningHour] Closed day cannot define opensAt or closesAt');
            }

            return;
        }

        if ($dto->opensAt === null || $dto->closesAt === null) {
            throw new ValidationFail('[CreateCompanyOpeningHour] Open day must define opensAt and closesAt');
        }

        $opensAt = $this->parseTime($dto->opensAt, 'opensAt');
        $closesAt = $this->parseTime($dto->closesAt, 'closesAt');

        if ($opensAt >= $closesAt) {
            throw new ValidationFail('[CreateCompanyOpeningHour] opensAt must be earlier than closesAt');
        }
    }

    private function assertOwnership(
        \App\Company\Domain\Entity\Company $company,
        ?\App\Company\Domain\Entity\Address\CompanyAddress $companyAddress,
        bool $addressWasExplicitlyProvided,
    ): void {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user) {
            throw new ValidationFail('[CreateCompanyOpeningHour] Authenticated user is required');
        }

        if ($user instanceof Tenant) {
            foreach ($user->getCompanies() as $tenantCompany) {
                if ($tenantCompany->getId()->equals($company->getId())) {
                    return;
                }
            }

            throw new ValidationFail('[CreateCompanyOpeningHour] Tenant cannot manage another company calendar');
        }

        if ($user instanceof Employee) {
            if (
                !$user->getCompany()
                || !$user->getCompanyAddress()
                || !$user->getCompany()->getId()->equals($company->getId())
            ) {
                throw new ValidationFail('[CreateCompanyOpeningHour] Employee cannot manage another company calendar');
            }

            if (!$addressWasExplicitlyProvided || !$companyAddress || !$user->getCompanyAddress()->getId()->equals($companyAddress->getId())) {
                throw new ValidationFail('[CreateCompanyOpeningHour] Employee can manage only their own company location calendar');
            }

            return;
        }

        throw new ValidationFail('[CreateCompanyOpeningHour] Only tenant or employee can manage company opening hours');
    }

    private function parseTime(string $value, string $field): \DateTimeImmutable
    {
        $time = \DateTimeImmutable::createFromFormat('H:i', $value)
            ?: \DateTimeImmutable::createFromFormat('H:i:s', $value);

        if (false === $time) {
            throw new ValidationFail(sprintf('[CreateCompanyOpeningHour] %s must be a valid time', $field));
        }

        return $time;
    }
}
