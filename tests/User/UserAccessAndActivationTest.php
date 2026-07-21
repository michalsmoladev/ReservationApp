<?php

declare(strict_types=1);

namespace App\Tests\User;

use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\User\Application\Command\ActivateTenant\ActivateTenantCommand;
use App\User\Application\Command\ActivateTenant\ActivateTenantValidator;
use App\User\Application\Exception\CustomerNotFoundException;
use App\User\Application\Query\GetCustomerById\GetCustomerByIdHandler;
use App\User\Application\Query\GetCustomerById\GetCustomerByIdQuery;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\Tenant\TenantRepositoryInterface;
use App\User\Domain\Entity\UserMetadata;
use App\User\Domain\Service\CustomerService;
use App\User\Presentation\Controller\EmployeeController;
use App\User\Presentation\Controller\TenantController;
use Psr\Log\NullLogger;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

final class UserAccessAndActivationTest
{
    public function run(): void
    {
        $this->testGuestReservationEndpointsArePublic();
        $this->testEmployeeActivationRouteHasLeadingSlash();
        $this->testTenantActivationRouteExistsAndHasLeadingSlash();
        $this->testActivateTenantUsesStringTokenMetadataFlow();
        $this->testActivateTenantRejectsExpiredToken();
        $this->testGetCustomerByIdThrowsWhenCustomerIsMissing();
    }

    private function testGuestReservationEndpointsArePublic(): void
    {
        $securityConfig = file_get_contents(__DIR__ . '/../../config/packages/security.yaml');

        self::assertNotFalse($securityConfig, 'Security config should be readable');
        self::assertContains("^/api/reservation/guest/cancel, roles: PUBLIC_ACCESS", $securityConfig);
        self::assertContains("^/api/reservation/guest, roles: PUBLIC_ACCESS", $securityConfig);
    }

    private function testEmployeeActivationRouteHasLeadingSlash(): void
    {
        $reflection = new \ReflectionMethod(EmployeeController::class, 'activeEmployeeAction');
        $route = $reflection->getAttributes(Route::class)[0]?->newInstance();

        self::assertSame('/api/employee/activate/{token}', $route->getPath());
    }

    private function testTenantActivationRouteExistsAndHasLeadingSlash(): void
    {
        $reflection = new \ReflectionMethod(TenantController::class, 'activeTenantAction');
        $route = $reflection->getAttributes(Route::class)[0]?->newInstance();

        self::assertSame('/api/tenant/activate/{token}', $route->getPath());
    }

    private function testActivateTenantUsesStringTokenMetadataFlow(): void
    {
        $tenant = $this->createTenant(
            token: 'tenant-activation-token',
            activationExpiresAt: new \DateTimeImmutable('+2 hours'),
            isActive: false,
        );
        $repository = new InMemoryTenantRepository([$tenant]);
        $validator = new ActivateTenantValidator($repository, new NullLogger());

        $validator(new ActivateTenantCommand(token: 'tenant-activation-token'));

        self::assertSame(['tenant-activation-token'], $repository->requestedTokens);
    }

    private function testActivateTenantRejectsExpiredToken(): void
    {
        $tenant = $this->createTenant(
            token: 'expired-token',
            activationExpiresAt: new \DateTimeImmutable('-1 hour'),
            isActive: false,
        );
        $validator = new ActivateTenantValidator(new InMemoryTenantRepository([$tenant]), new NullLogger());

        self::assertThrows(
            static fn () => $validator(new ActivateTenantCommand(token: 'expired-token')),
            ValidationFail::class,
            'Expired tenant activation token should be rejected',
        );
    }

    private function testGetCustomerByIdThrowsWhenCustomerIsMissing(): void
    {
        $handler = new GetCustomerByIdHandler(
            customerRepository: new InMemoryCustomerRepository([]),
            customerService: new CustomerService(),
        );

        self::assertThrows(
            static fn () => $handler(new GetCustomerByIdQuery(Uuid::v7())),
            CustomerNotFoundException::class,
            'Missing customer should raise domain exception',
        );
    }

    private function createTenant(string $token, \DateTimeImmutable $activationExpiresAt, bool $isActive): Tenant
    {
        $tenant = new Tenant(
            email: 'tenant@example.com',
            password: 'secret',
            metadata: new UserMetadata($token, $activationExpiresAt),
            isActive: $isActive,
            firstname: 'Tenant',
            lastname: 'Owner',
        );
        $tenant->setUuid(Uuid::v7());

        return $tenant;
    }

    private static function assertContains(string $needle, string $haystack, string $message = 'Expected string to contain fragment'): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new \RuntimeException(sprintf('%s. Missing fragment: %s', $message, $needle));
        }
    }

    private static function assertNotFalse(mixed $value, string $message): void
    {
        if (false === $value) {
            throw new \RuntimeException($message);
        }
    }

    private static function assertSame(mixed $expected, mixed $actual, string $message = 'Values are not identical'): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException(sprintf('%s. Expected %s, got %s', $message, var_export($expected, true), var_export($actual, true)));
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

final class InMemoryTenantRepository implements TenantRepositoryInterface
{
    /** @var Tenant[] */
    private array $tenants;

    /** @var string[] */
    public array $requestedTokens = [];

    /**
     * @param Tenant[] $tenants
     */
    public function __construct(array $tenants)
    {
        $this->tenants = $tenants;
    }

    public function findByEmail(string $email): ?Tenant
    {
        foreach ($this->tenants as $tenant) {
            if ($tenant->getEmail() === $email) {
                return $tenant;
            }
        }

        return null;
    }

    public function findById(Uuid $uuid): ?Tenant
    {
        foreach ($this->tenants as $tenant) {
            if ($tenant->getUuid()->equals($uuid)) {
                return $tenant;
            }
        }

        return null;
    }

    public function save(Tenant $employee): void
    {
    }

    public function lock(Uuid $uuid): void
    {
    }

    public function remove(Tenant $Employee): void
    {
    }

    public function findByToken(string $token): ?Tenant
    {
        $this->requestedTokens[] = $token;

        foreach ($this->tenants as $tenant) {
            if ($tenant->getMetadata()->getActivationToken() === $token) {
                return $tenant;
            }
        }

        return null;
    }
}

final class InMemoryCustomerRepository implements CustomerRepositoryInterface
{
    /** @var Customer[] */
    private array $customers;

    /**
     * @param Customer[] $customers
     */
    public function __construct(array $customers)
    {
        $this->customers = $customers;
    }

    public function save(Customer $customer): void
    {
        $this->customers[$customer->getUuid()->toString()] = $customer;
    }

    public function findById(Uuid $id): ?Customer
    {
        foreach ($this->customers as $customer) {
            if ($customer->getUuid()->equals($id)) {
                return $customer;
            }
        }

        return null;
    }

    public function findByIds(array $ids): array
    {
        return [];
    }

    public function findByEmail(string $email): ?Customer
    {
        foreach ($this->customers as $customer) {
            if ($customer->getEmail() === $email) {
                return $customer;
            }
        }

        return null;
    }

    public function remove(Customer $customer): void
    {
    }

    public function findByToken(string $token): ?Customer
    {
        foreach ($this->customers as $customer) {
            if ($customer->getMetadata()->getActivationToken() === $token) {
                return $customer;
            }
        }

        return null;
    }
}
