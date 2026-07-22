<?php

declare(strict_types=1);

namespace App\Tests\Company;

use App\Company\Application\Command\CreateCompanyAddress\CreateCompanyAddressCommand;
use App\Company\Application\Command\CreateCompanyAddress\CreateCompanyAddressHandler;
use App\Company\Application\Command\CreateCompanyAddress\CreateCompanyAddressValidator;
use App\Company\Application\Command\CreateCompanyAddress\DTO\CreateCompanyAddressDTO;
use App\Company\Application\Command\DeactivateCompany\DeactivateCompanyCommand;
use App\Company\Application\Command\DeactivateCompany\DeactivateCompanyHandler;
use App\Company\Application\Command\DeactivateCompany\DeactivateCompanyValidator;
use App\Company\Application\Command\DeleteCompanyAddress\DeleteCompanyAddressCommand;
use App\Company\Application\Command\DeleteCompanyAddress\DeleteCompanyAddressHandler;
use App\Company\Application\Command\DeleteCompanyAddress\DeleteCompanyAddressValidator;
use App\Company\Application\Command\UpdateCompanyAddress\DTO\UpdateCompanyAddressDTO;
use App\Company\Application\Command\UpdateCompanyAddress\UpdateCompanyAddressCommand;
use App\Company\Application\Command\UpdateCompanyAddress\UpdateCompanyAddressHandler;
use App\Company\Application\Command\UpdateCompanyAddress\UpdateCompanyAddressValidator;
use App\Company\Application\Command\UpdateCompany\DTO\UpdateCompanyDTO;
use App\Company\Application\Command\UpdateCompany\UpdateCompanyCommand;
use App\Company\Application\Command\UpdateCompany\UpdateCompanyHandler;
use App\Company\Application\Command\UpdateCompany\UpdateCompanyValidator;
use App\Company\Application\Exception\CompanyNotFoundException;
use App\Company\Application\Query\GetCompanyAddresses\GetCompanyAddressesHandler;
use App\Company\Application\Query\GetCompanyAddresses\GetCompanyAddressesQuery;
use App\Company\Application\Query\GetCompanyAddresses\GetCompanyAddressesValidator;
use App\Company\Application\Factory\CompanyAddressFactory;
use App\Company\Domain\Entity\Address\CompanyAddressRepositoryInterface;
use App\Company\Application\Query\GetCompanies\GetCompaniesHandler;
use App\Company\Application\Query\GetCompanies\GetCompaniesQuery;
use App\Company\Application\Query\GetCompanyById\GetCompanyByIdHandler;
use App\Company\Application\Query\GetCompanyById\GetCompanyByIdQuery;
use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Company;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Company\Domain\Service\CompanyService;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Domain\Entity\CompanyOpeningHour;
use App\Reservation\Domain\Entity\CompanyOpeningHour\CompanyOpeningHourRepositoryInterface;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Service;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\UserMetadata;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

final class CompanyManagementTest
{
    public function run(): void
    {
        $this->testTenantCanListOnlyOwnedCompanies();
        $this->testTenantCanViewOwnedCompany();
        $this->testTenantCannotViewForeignCompany();
        $this->testTenantCanUpdateOwnedCompany();
        $this->testTenantCannotUpdateForeignCompany();
        $this->testTenantCanDeactivateOwnedCompanyWithoutBlockingDependencies();
        $this->testTenantCannotDeactivateForeignCompany();
        $this->testTenantCannotDeactivateCompanyWithActiveService();
        $this->testTenantCannotDeactivateCompanyWithActiveEmployee();
        $this->testTenantCannotDeactivateCompanyWithActiveReservation();
        $this->testTenantCannotDeactivateCompanyWithOpeningHours();
        $this->testInactiveCompanyIsHiddenFromReadApis();
        $this->testTenantCanListCompanyAddresses();
        $this->testTenantCanCreateCompanyAddress();
        $this->testTenantCannotCreateCompanyAddressForForeignCompany();
        $this->testTenantCanUpdateCompanyAddress();
        $this->testTenantCannotUpdateForeignCompanyAddress();
        $this->testTenantCanDeleteUnusedCompanyAddress();
        $this->testTenantCannotDeleteUsedCompanyAddress();
    }

    private function testTenantCanListOnlyOwnedCompanies(): void
    {
        $ownedCompany = $this->createCompany('Owned Company');
        $foreignCompany = $this->createCompany('Foreign Company');
        $tenant = $this->createTenant([$ownedCompany]);

        $handler = new GetCompaniesHandler(
            companyService: new CompanyService(),
            security: $this->securityForUser($tenant),
        );

        $result = $handler(new GetCompaniesQuery());

        self::assertSame(1, \count($result->companies));
        self::assertSame($ownedCompany->getId()->toString(), $result->companies[0]->id);
        self::assertSame('Owned Company', $result->companies[0]->displayName);
        self::assertTrue($ownedCompany->getId()->toString() !== $foreignCompany->getId()->toString());
    }

    private function testTenantCanViewOwnedCompany(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);

        $handler = new GetCompanyByIdHandler(
            companyRepository: new InMemoryCompanyRepository([$company]),
            companyService: new CompanyService(),
            security: $this->securityForUser($tenant),
        );

        $result = $handler(new GetCompanyByIdQuery($company->getId()));

        self::assertSame($company->getId()->toString(), $result->id);
        self::assertSame('Owned Company', $result->displayName);
        self::assertSame(1, \count($result->addresses));
    }

    private function testTenantCannotViewForeignCompany(): void
    {
        $ownedCompany = $this->createCompany('Owned Company');
        $foreignCompany = $this->createCompany('Foreign Company');
        $tenant = $this->createTenant([$ownedCompany]);

        $handler = new GetCompanyByIdHandler(
            companyRepository: new InMemoryCompanyRepository([$ownedCompany, $foreignCompany]),
            companyService: new CompanyService(),
            security: $this->securityForUser($tenant),
        );

        self::assertThrows(
            static fn () => $handler(new GetCompanyByIdQuery($foreignCompany->getId())),
            CompanyNotFoundException::class,
            'Tenant should not see foreign company',
        );
    }

    private function testTenantCanUpdateOwnedCompany(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);
        $repository = new InMemoryCompanyRepository([$company]);
        $command = new UpdateCompanyCommand(
            companyId: $company->getId(),
            updateCompanyDTO: new UpdateCompanyDTO(
                displayName: ' Updated Name ',
                legalName: ' Updated Legal ',
                taxId: '9876543210',
                currency: 'eur',
            ),
        );

        (new UpdateCompanyValidator(
            companyRepository: $repository,
            security: $this->securityForUser($tenant),
        ))($command);

        (new UpdateCompanyHandler(
            companyRepository: $repository,
            logger: new NullLogger(),
        ))($command);

        $updated = $repository->findById($company->getId());

        self::assertSame('Updated Name', $updated?->getDisplayName());
        self::assertSame('Updated Legal', $updated?->getLegalName());
        self::assertSame('9876543210', $updated?->getTaxId());
        self::assertSame('EUR', $updated?->getCurrency());
    }

    private function testTenantCannotUpdateForeignCompany(): void
    {
        $ownedCompany = $this->createCompany('Owned Company');
        $foreignCompany = $this->createCompany('Foreign Company');
        $tenant = $this->createTenant([$ownedCompany]);
        $repository = new InMemoryCompanyRepository([$ownedCompany, $foreignCompany]);
        $validator = new UpdateCompanyValidator(
            companyRepository: $repository,
            security: $this->securityForUser($tenant),
        );

        self::assertThrows(
            static fn () => $validator(new UpdateCompanyCommand(
                companyId: $foreignCompany->getId(),
                updateCompanyDTO: new UpdateCompanyDTO(
                    displayName: 'Foreign',
                    legalName: 'Foreign',
                    taxId: '111',
                    currency: 'PLN',
                ),
            )),
            ValidationFail::class,
            'Tenant should not update foreign company',
        );
    }

    private function testTenantCanDeactivateOwnedCompanyWithoutBlockingDependencies(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);
        $repository = new InMemoryCompanyRepository([$company]);
        $command = new DeactivateCompanyCommand($company->getId());

        (new DeactivateCompanyValidator(
            companyRepository: $repository,
            serviceRepository: new InMemoryServiceRepository(false),
            employeeRepository: new InMemoryEmployeeRepository(false),
            reservationRepository: new InMemoryReservationRepository(false),
            companyOpeningHourRepository: new InMemoryCompanyOpeningHourRepository(false),
            security: $this->securityForUser($tenant),
        ))($command);

        (new DeactivateCompanyHandler(
            companyRepository: $repository,
            logger: new NullLogger(),
        ))($command);

        self::assertTrue($repository->findById($company->getId())?->isActive() === false, 'Company should be inactive after deactivation');
    }

    private function testTenantCannotDeactivateForeignCompany(): void
    {
        $ownedCompany = $this->createCompany('Owned Company');
        $foreignCompany = $this->createCompany('Foreign Company');
        $tenant = $this->createTenant([$ownedCompany]);

        self::assertThrows(
            static fn () => (new DeactivateCompanyValidator(
                companyRepository: new InMemoryCompanyRepository([$ownedCompany, $foreignCompany]),
                serviceRepository: new InMemoryServiceRepository(false),
                employeeRepository: new InMemoryEmployeeRepository(false),
                reservationRepository: new InMemoryReservationRepository(false),
                companyOpeningHourRepository: new InMemoryCompanyOpeningHourRepository(false),
                security: self::securityForStaticUser($tenant),
            ))(new DeactivateCompanyCommand($foreignCompany->getId())),
            ValidationFail::class,
            'Tenant should not deactivate foreign company',
        );
    }

    private function testTenantCannotDeactivateCompanyWithActiveService(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);

        self::assertThrows(
            static fn () => (new DeactivateCompanyValidator(
                companyRepository: new InMemoryCompanyRepository([$company]),
                serviceRepository: new InMemoryServiceRepository(true),
                employeeRepository: new InMemoryEmployeeRepository(false),
                reservationRepository: new InMemoryReservationRepository(false),
                companyOpeningHourRepository: new InMemoryCompanyOpeningHourRepository(false),
                security: self::securityForStaticUser($tenant),
            ))(new DeactivateCompanyCommand($company->getId())),
            ValidationFail::class,
            'Active services should block company deactivation',
        );
    }

    private function testTenantCannotDeactivateCompanyWithActiveEmployee(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);

        self::assertThrows(
            static fn () => (new DeactivateCompanyValidator(
                companyRepository: new InMemoryCompanyRepository([$company]),
                serviceRepository: new InMemoryServiceRepository(false),
                employeeRepository: new InMemoryEmployeeRepository(true),
                reservationRepository: new InMemoryReservationRepository(false),
                companyOpeningHourRepository: new InMemoryCompanyOpeningHourRepository(false),
                security: self::securityForStaticUser($tenant),
            ))(new DeactivateCompanyCommand($company->getId())),
            ValidationFail::class,
            'Active employees should block company deactivation',
        );
    }

    private function testTenantCannotDeactivateCompanyWithActiveReservation(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);

        self::assertThrows(
            static fn () => (new DeactivateCompanyValidator(
                companyRepository: new InMemoryCompanyRepository([$company]),
                serviceRepository: new InMemoryServiceRepository(false),
                employeeRepository: new InMemoryEmployeeRepository(false),
                reservationRepository: new InMemoryReservationRepository(true),
                companyOpeningHourRepository: new InMemoryCompanyOpeningHourRepository(false),
                security: self::securityForStaticUser($tenant),
            ))(new DeactivateCompanyCommand($company->getId())),
            ValidationFail::class,
            'Active reservations should block company deactivation',
        );
    }

    private function testTenantCannotDeactivateCompanyWithOpeningHours(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);

        self::assertThrows(
            static fn () => (new DeactivateCompanyValidator(
                companyRepository: new InMemoryCompanyRepository([$company]),
                serviceRepository: new InMemoryServiceRepository(false),
                employeeRepository: new InMemoryEmployeeRepository(false),
                reservationRepository: new InMemoryReservationRepository(false),
                companyOpeningHourRepository: new InMemoryCompanyOpeningHourRepository(true),
                security: self::securityForStaticUser($tenant),
            ))(new DeactivateCompanyCommand($company->getId())),
            ValidationFail::class,
            'Opening hours should block company deactivation',
        );
    }

    private function testInactiveCompanyIsHiddenFromReadApis(): void
    {
        $company = $this->createCompany('Owned Company');
        $company->deactivate();
        $tenant = $this->createTenant([$company]);
        $repository = new InMemoryCompanyRepository([$company]);

        $listResult = (new GetCompaniesHandler(
            companyService: new CompanyService(),
            security: $this->securityForUser($tenant),
        ))(new GetCompaniesQuery());

        self::assertSame(0, \count($listResult->companies), 'Inactive company should not appear in list');

        self::assertThrows(
            static fn () => (new GetCompanyByIdHandler(
                companyRepository: $repository,
                companyService: new CompanyService(),
                security: self::securityForStaticUser($tenant),
            ))(new GetCompanyByIdQuery($company->getId())),
            CompanyNotFoundException::class,
            'Inactive company should not be visible in details API',
        );

        self::assertThrows(
            static fn () => (new GetCompanyAddressesHandler(
                companyRepository: $repository,
                companyAddressRepository: new InMemoryCompanyAddressRepository($company->getAddresses()->toArray()),
                companyService: new CompanyService(),
                security: self::securityForStaticUser($tenant),
            ))(new GetCompanyAddressesQuery($company->getId())),
            CompanyNotFoundException::class,
            'Inactive company should not expose addresses API',
        );
    }

    private function testTenantCanListCompanyAddresses(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);
        $companyRepository = new InMemoryCompanyRepository([$company]);
        $addressRepository = new InMemoryCompanyAddressRepository($company->getAddresses()->toArray());

        (new GetCompanyAddressesValidator(
            security: $this->securityForUser($tenant),
        ))(new GetCompanyAddressesQuery($company->getId()));

        $result = (new GetCompanyAddressesHandler(
            companyRepository: $companyRepository,
            companyAddressRepository: $addressRepository,
            companyService: new CompanyService(),
            security: $this->securityForUser($tenant),
        ))(new GetCompanyAddressesQuery($company->getId()));

        self::assertSame(1, \count($result->addresses));
        self::assertSame('HQ', $result->addresses[0]->name);
    }

    private function testTenantCanCreateCompanyAddress(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);
        $companyRepository = new InMemoryCompanyRepository([$company]);
        $addressRepository = new InMemoryCompanyAddressRepository($company->getAddresses()->toArray());
        $command = new CreateCompanyAddressCommand(
            companyId: $company->getId(),
            addressId: Uuid::v7(),
            createCompanyAddressDTO: new CreateCompanyAddressDTO(
                street: 'New Street',
                city: 'Warsaw',
                apartmentNo: 5,
                buildingNo: 10,
                postalCode: '00-002',
                country: 'PL',
                name: 'Branch',
            ),
        );

        (new CreateCompanyAddressValidator(
            companyRepository: $companyRepository,
            security: $this->securityForUser($tenant),
        ))($command);

        (new CreateCompanyAddressHandler(
            companyRepository: $companyRepository,
            companyAddressRepository: $addressRepository,
            companyAddressFactory: new CompanyAddressFactory(),
            logger: new NullLogger(),
        ))($command);

        self::assertSame(2, \count($addressRepository->findByCompanyId($company->getId())));
    }

    private function testTenantCannotCreateCompanyAddressForForeignCompany(): void
    {
        $ownedCompany = $this->createCompany('Owned Company');
        $foreignCompany = $this->createCompany('Foreign Company');
        $tenant = $this->createTenant([$ownedCompany]);
        $companyRepository = new InMemoryCompanyRepository([$ownedCompany, $foreignCompany]);

        self::assertThrows(
            static fn () => (new CreateCompanyAddressValidator(
                companyRepository: $companyRepository,
                security: self::securityForStaticUser($tenant),
            ))(new CreateCompanyAddressCommand(
                companyId: $foreignCompany->getId(),
                addressId: Uuid::v7(),
                createCompanyAddressDTO: new CreateCompanyAddressDTO(
                    street: 'New Street',
                    city: 'Warsaw',
                    apartmentNo: 5,
                    buildingNo: 10,
                    postalCode: '00-002',
                    country: 'PL',
                    name: 'Branch',
                ),
            )),
            ValidationFail::class,
            'Tenant should not create address for foreign company',
        );
    }

    private function testTenantCanUpdateCompanyAddress(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);
        /** @var CompanyAddress $address */
        $address = $company->getAddresses()->first();
        $addressRepository = new InMemoryCompanyAddressRepository([$address]);
        $command = new UpdateCompanyAddressCommand(
            companyAddressId: $address->getId(),
            updateCompanyAddressDTO: new UpdateCompanyAddressDTO(
                street: ' Updated Street ',
                city: ' Updated City ',
                apartmentNo: 3,
                buildingNo: 11,
                postalCode: '00-003',
                country: ' DE ',
                name: ' Branch ',
            ),
        );

        (new UpdateCompanyAddressValidator(
            companyAddressRepository: $addressRepository,
            security: $this->securityForUser($tenant),
        ))($command);

        (new UpdateCompanyAddressHandler(
            companyAddressRepository: $addressRepository,
            logger: new NullLogger(),
        ))($command);

        $updated = $addressRepository->findById($address->getId());
        self::assertSame('Updated Street', $updated?->getStreet());
        self::assertSame('Updated City', $updated?->getCity());
        self::assertSame('DE', $updated?->getCountry());
        self::assertSame('Branch', $updated?->getName());
    }

    private function testTenantCannotUpdateForeignCompanyAddress(): void
    {
        $ownedCompany = $this->createCompany('Owned Company');
        $foreignCompany = $this->createCompany('Foreign Company');
        $tenant = $this->createTenant([$ownedCompany]);
        /** @var CompanyAddress $foreignAddress */
        $foreignAddress = $foreignCompany->getAddresses()->first();
        $addressRepository = new InMemoryCompanyAddressRepository([$foreignAddress]);

        self::assertThrows(
            static fn () => (new UpdateCompanyAddressValidator(
                companyAddressRepository: $addressRepository,
                security: self::securityForStaticUser($tenant),
            ))(new UpdateCompanyAddressCommand(
                companyAddressId: $foreignAddress->getId(),
                updateCompanyAddressDTO: new UpdateCompanyAddressDTO(
                    street: 'Street',
                    city: 'City',
                    apartmentNo: 1,
                    buildingNo: 2,
                    postalCode: '00-001',
                    country: 'PL',
                    name: 'HQ',
                ),
            )),
            ValidationFail::class,
            'Tenant should not update foreign company address',
        );
    }

    private function testTenantCanDeleteUnusedCompanyAddress(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);
        /** @var CompanyAddress $address */
        $address = $company->getAddresses()->first();
        $addressRepository = new InMemoryCompanyAddressRepository([$address]);
        $addressRepository->isUsed = false;
        $command = new DeleteCompanyAddressCommand($address->getId());

        (new DeleteCompanyAddressValidator(
            companyAddressRepository: $addressRepository,
            security: $this->securityForUser($tenant),
        ))($command);

        (new DeleteCompanyAddressHandler(
            companyAddressRepository: $addressRepository,
            logger: new NullLogger(),
        ))($command);

        self::assertSame(null, $addressRepository->findById($address->getId()));
    }

    private function testTenantCannotDeleteUsedCompanyAddress(): void
    {
        $company = $this->createCompany('Owned Company');
        $tenant = $this->createTenant([$company]);
        /** @var CompanyAddress $address */
        $address = $company->getAddresses()->first();
        $addressRepository = new InMemoryCompanyAddressRepository([$address]);
        $addressRepository->isUsed = true;

        self::assertThrows(
            static fn () => (new DeleteCompanyAddressValidator(
                companyAddressRepository: $addressRepository,
                security: self::securityForStaticUser($tenant),
            ))(new DeleteCompanyAddressCommand($address->getId())),
            ValidationFail::class,
            'Tenant should not delete used company address',
        );
    }

    private function createCompany(string $displayName): Company
    {
        $company = new Company(
            displayName: $displayName,
            legalName: $displayName . ' LLC',
            taxId: '1234567890',
            currency: 'PLN',
        );
        $company->setId(Uuid::v7());

        $address = new CompanyAddress(
            street: 'Street',
            city: 'City',
            country: 'PL',
            postalCode: '00-001',
            apartmentNo: 1,
            buildingNo: 2,
            name: 'HQ',
        );
        $company->addAddress($address);

        $this->setPrivateProperty($company, 'createdAt', new \DateTimeImmutable('2026-07-21T10:00:00+00:00'));
        $this->setPrivateProperty($company, 'updatedAt', null);
        $this->setPrivateProperty($address, 'id', Uuid::v7());
        $this->setPrivateProperty($address, 'createdAt', new \DateTimeImmutable('2026-07-21T10:00:00+00:00'));
        $this->setPrivateProperty($address, 'updatedAt', null);

        return $company;
    }

    /**
     * @param Company[] $companies
     */
    private function createTenant(array $companies): Tenant
    {
        $tenant = new Tenant(
            email: 'tenant@example.com',
            password: 'secret',
            metadata: new UserMetadata(Uuid::v7()->toString(), new \DateTimeImmutable('+2 hours')),
            isActive: true,
            firstname: 'Tenant',
            lastname: 'Owner',
        );
        $tenant->setUuid(Uuid::v7());
        $tenant->setRoles(['ROLE_TENANT']);
        $this->setPrivateProperty($tenant, 'createdAt', new \DateTimeImmutable('2026-07-21T10:00:00+00:00'));
        $this->setPrivateProperty($tenant, 'updatedAt', null);

        foreach ($companies as $company) {
            $tenant->addCompany($company);
        }

        return $tenant;
    }

    private function securityForUser(?UserInterface $user): Security
    {
        return self::securityForStaticUser($user);
    }

    private static function securityForStaticUser(?UserInterface $user): Security
    {
        $token = new class($user) implements TokenInterface {
            private array $attributes = [];

            public function __construct(private ?UserInterface $user)
            {
            }

            public function __toString(): string
            {
                return 'test-token';
            }

            public function getUserIdentifier(): string
            {
                return $this->user?->getUserIdentifier() ?? '';
            }

            public function getRoleNames(): array
            {
                return $this->user?->getRoles() ?? [];
            }

            public function getUser(): ?UserInterface
            {
                return $this->user;
            }

            public function setUser(UserInterface $user): void
            {
                $this->user = $user;
            }

            public function eraseCredentials(): void
            {
            }

            public function getAttributes(): array
            {
                return $this->attributes;
            }

            public function setAttributes(array $attributes): void
            {
                $this->attributes = $attributes;
            }

            public function hasAttribute(string $name): bool
            {
                return \array_key_exists($name, $this->attributes);
            }

            public function getAttribute(string $name): mixed
            {
                return $this->attributes[$name] ?? null;
            }

            public function setAttribute(string $name, mixed $value): void
            {
                $this->attributes[$name] = $value;
            }

            public function __serialize(): array
            {
                return [];
            }

            public function __unserialize(array $data): void
            {
            }
        };

        $tokenStorage = new class($token) implements TokenStorageInterface {
            public function __construct(private ?TokenInterface $token)
            {
            }

            public function getToken(): ?TokenInterface
            {
                return $this->token;
            }

            public function setToken(?TokenInterface $token = null): void
            {
                $this->token = $token;
            }
        };

        $container = new class($tokenStorage) implements ContainerInterface {
            public function __construct(private readonly TokenStorageInterface $tokenStorage)
            {
            }

            public function get(string $id): mixed
            {
                if ('security.token_storage' === $id) {
                    return $this->tokenStorage;
                }

                throw new \RuntimeException(sprintf('Unexpected service lookup: %s', $id));
            }

            public function has(string $id): bool
            {
                return 'security.token_storage' === $id;
            }
        };

        return new Security($container);
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionProperty($object, $property);
        $reflection->setValue($object, $value);
    }

    private static function assertSame(mixed $expected, mixed $actual, string $message = 'Values are not identical'): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException(sprintf('%s. Expected %s, got %s', $message, var_export($expected, true), var_export($actual, true)));
        }
    }

    private static function assertTrue(bool $condition, string $message = 'Expected true'): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    private static function assertThrows(callable $callback, string $exceptionClass, string $message): void
    {
        try {
            $callback();
        } catch (\Throwable $throwable) {
            if ($throwable instanceof $exceptionClass) {
                return;
            }

            throw new \RuntimeException(
                sprintf('%s. Expected %s, got %s', $message, $exceptionClass, $throwable::class),
                previous: $throwable,
            );
        }

        throw new \RuntimeException(sprintf('%s. Expected exception %s was not thrown', $message, $exceptionClass));
    }
}

final class InMemoryCompanyRepository implements CompanyRepositoryInterface
{
    /**
     * @param Company[] $companies
     */
    public function __construct(private array $companies)
    {
    }

    public function save(Company $company): void
    {
        $this->companies[$company->getId()->toString()] = $company;
    }

    public function findById(Uuid $id): ?Company
    {
        foreach ($this->companies as $company) {
            if ($company->getId()->equals($id)) {
                return $company;
            }
        }

        return null;
    }

    public function findByName(string $name): ?Company
    {
        foreach ($this->companies as $company) {
            if ($company->getDisplayName() === $name) {
                return $company;
            }
        }

        return null;
    }
}

final class InMemoryCompanyAddressRepository implements CompanyAddressRepositoryInterface
{
    public bool $isUsed = false;
    private array $addresses = [];

    /**
     * @param CompanyAddress[] $addresses
     */
    public function __construct(array $addresses)
    {
        foreach ($addresses as $address) {
            $this->addresses[$address->getId()->toString()] = $address;
        }
    }

    public function findById(Uuid $id): ?CompanyAddress
    {
        foreach ($this->addresses as $address) {
            if ($address->getId()->equals($id)) {
                return $address;
            }
        }

        return null;
    }

    public function findByCompanyId(Uuid $companyId): array
    {
        return array_values(array_filter(
            $this->addresses,
            static fn (CompanyAddress $address): bool => null !== $address->getCompany() && $address->getCompany()->getId()->equals($companyId),
        ));
    }

    public function save(CompanyAddress $companyAddress): void
    {
        $this->addresses[$companyAddress->getId()->toString()] = $companyAddress;
    }

    public function remove(CompanyAddress $companyAddress): void
    {
        unset($this->addresses[$companyAddress->getId()->toString()]);
    }

    public function isUsed(Uuid $companyAddressId): bool
    {
        return $this->isUsed;
    }
}

final class InMemoryServiceRepository implements ServiceRepositoryInterface
{
    public function __construct(private readonly bool $hasActiveServices)
    {
    }

    public function save(Service $service): void
    {
    }

    public function findById(Uuid $id): ?Service
    {
        return null;
    }

    public function findByFilters(?Uuid $companyId, ?Uuid $companyAddressId, bool $onlyActive = true): array
    {
        return [];
    }

    public function findByIds(array $ids): array
    {
        return [];
    }

    public function existsActiveByCompanyId(Uuid $companyId): bool
    {
        return $this->hasActiveServices;
    }
}

final class InMemoryEmployeeRepository implements EmployeeRepositoryInterface
{
    public function __construct(private readonly bool $hasActiveEmployees)
    {
    }

    public function findByEmail(string $email): ?\App\User\Domain\Entity\Employee\Employee
    {
        return null;
    }

    public function findById(Uuid $uuid): ?\App\User\Domain\Entity\Employee\Employee
    {
        return null;
    }

    public function findByIds(array $uuids): array
    {
        return [];
    }

    public function save(\App\User\Domain\Entity\Employee\Employee $employee): void
    {
    }

    public function lock(Uuid $uuid): void
    {
    }

    public function remove(\App\User\Domain\Entity\Employee\Employee $Employee): void
    {
    }

    public function findByToken(string $token): ?\App\User\Domain\Entity\Employee\Employee
    {
        return null;
    }

    public function existsActiveByCompanyId(Uuid $companyId): bool
    {
        return $this->hasActiveEmployees;
    }
}

final class InMemoryReservationRepository implements ReservationRepositoryInterface
{
    public function __construct(private readonly bool $hasActiveReservations)
    {
    }

    public function findById(Uuid $id): ?Reservation
    {
        return null;
    }

    public function findByGuestCancellationToken(string $guestCancellationToken): ?Reservation
    {
        return null;
    }

    public function findByFilters(?Uuid $companyId, ?Uuid $companyAddressId, ?Uuid $employeeId, ?Uuid $customerId, ?\DateTimeImmutable $from, ?\DateTimeImmutable $to, ?string $status, ?array $companyIds = null): array
    {
        return [];
    }

    public function findActiveByEmployeesAndDateRange(array $employeeIds, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return [];
    }

    public function employeeHasReservationConflict(Uuid $employeeId, \DateTimeImmutable $reservationDate, float $serviceDuration): bool
    {
        return false;
    }

    public function claimGuestReservationsByEmail(Uuid $customerId, string $email): int
    {
        return 0;
    }

    public function save(Reservation $reservation): void
    {
    }

    public function existsActiveByCompanyId(Uuid $companyId): bool
    {
        return $this->hasActiveReservations;
    }
}

final class InMemoryCompanyOpeningHourRepository implements CompanyOpeningHourRepositoryInterface
{
    public function __construct(private readonly bool $hasOpeningHours)
    {
    }

    public function save(CompanyOpeningHour $companyOpeningHour): void
    {
    }

    public function existsForDay(Uuid $companyId, int $dayOfWeek, ?Uuid $companyAddressId = null): bool
    {
        return false;
    }

    public function findByCompanyAndDateRange(Uuid $companyId, \DateTimeImmutable $from, \DateTimeImmutable $to, ?Uuid $companyAddressId = null): array
    {
        return [];
    }

    public function existsByCompanyId(Uuid $companyId): bool
    {
        return $this->hasOpeningHours;
    }
}
