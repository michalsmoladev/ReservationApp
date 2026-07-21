<?php

declare(strict_types=1);

namespace App\Tests\Company;

use App\Company\Application\Command\UpdateCompany\DTO\UpdateCompanyDTO;
use App\Company\Application\Command\UpdateCompany\UpdateCompanyCommand;
use App\Company\Application\Command\UpdateCompany\UpdateCompanyHandler;
use App\Company\Application\Command\UpdateCompany\UpdateCompanyValidator;
use App\Company\Application\Exception\CompanyNotFoundException;
use App\Company\Application\Query\GetCompanies\GetCompaniesHandler;
use App\Company\Application\Query\GetCompanies\GetCompaniesQuery;
use App\Company\Application\Query\GetCompanyById\GetCompanyByIdHandler;
use App\Company\Application\Query\GetCompanyById\GetCompanyByIdQuery;
use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Company;
use App\Company\Domain\Entity\CompanyRepositoryInterface;
use App\Company\Domain\Service\CompanyService;
use App\Core\Application\MessageBus\Exception\ValidationFail;
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
