<?php

declare(strict_types=1);

namespace App\Tests\Reservation;

use App\Mailer\Application\Command\SendGuestCancellationLink\SendGuestCancellationLinkCommand;
use App\Company\Domain\Entity\Address\CompanyAddress;
use App\Company\Domain\Entity\Company;
use App\Core\Application\MessageBus\Exception\ValidationFail;
use App\Reservation\Application\AcceptReservation\AcceptReservationCommand;
use App\Reservation\Application\AcceptReservation\AcceptReservationValidator;
use App\Reservation\Application\Availability\ReservationAvailabilityChecker;
use App\Reservation\Application\CancelReservation\CancelReservationCommand;
use App\Reservation\Application\CancelReservation\CancelReservationValidator;
use App\Reservation\Application\CreateGuestReservation\CreateGuestReservationCommand;
use App\Reservation\Application\CreateGuestReservation\CreateGuestReservationHandler;
use App\Reservation\Application\CreateGuestReservation\DTO\CreateGuestReservationDTO;
use App\Reservation\Application\CreateReservation\CreateReservationCommand;
use App\Reservation\Application\CreateReservation\CreateReservationHandler;
use App\Reservation\Application\CreateReservation\DTO\CreateReservationDTO;
use App\Reservation\Application\Factory\ReservationFactory;
use App\Reservation\Domain\Entity\Reservation;
use App\Reservation\Domain\Entity\Reservation\ReservationRepositoryInterface;
use App\Reservation\Domain\Entity\Service;
use App\Reservation\Domain\Entity\Service\ServiceRepositoryInterface;
use App\Reservation\Infrastructure\ReservationRepository;
use App\User\Domain\Entity\Customer\Customer;
use App\User\Domain\Entity\Customer\CustomerRepositoryInterface;
use App\User\Domain\Entity\Employee\Employee;
use App\User\Domain\Entity\Employee\EmployeeRepositoryInterface;
use App\User\Domain\Entity\Tenant\Tenant;
use App\User\Domain\Entity\UserMetadata;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Uid\Uuid;

final class ReservationFlowTest
{
    public function run(): void
    {
        $this->testReservationSnapshotsServicePriceAndDuration();
        $this->testConflictCheckDetectsOverlap();
        $this->testCanceledReservationDoesNotBlockConflictCheck();
        $this->testCreateReservationAutoAssignsAvailableEmployee();
        $this->testAcceptReservationPermissions();
        $this->testCancelReservationPermissions();
        $this->testGuestReservationStoresGuestDataAndCancellationToken();
        $this->testGuestReservationDispatchesCancellationLinkCommand();
        $this->testGuestReservationResponseDoesNotExposeCancellationToken();
    }

    private function testReservationSnapshotsServicePriceAndDuration(): void
    {
        $service = $this->createService(duration: 45.0, price: 120.0);
        $customer = $this->createCustomer('customer@example.com');
        $employee = $this->createEmployee($service->getCompany(), $service->getCompanyAddress(), 'employee@example.com');
        $service->addEmployee($employee);

        $factory = new ReservationFactory();
        $reservation = $factory->createForCustomer(
            reservationDTO: new CreateReservationDTO(
                serviceId: $service->getId()->toString(),
                customerId: $customer->getUuid()->toString(),
                reservationDate: '+3 days',
                employeeId: $employee->getUuid()->toString(),
                note: '  note  ',
            ),
            id: Uuid::v7(),
            service: $service,
            customer: $customer,
            employee: $employee,
        );

        $service->update([
            'duration' => 60.0,
            'price' => 180.0,
        ]);

        self::assertSame(120.0, $reservation->getServicePrice(), 'Reservation should snapshot service price');
        self::assertSame(45.0, $reservation->getServiceDuration(), 'Reservation should snapshot service duration');
        self::assertSame('note', $reservation->getNote(), 'Reservation note should be trimmed');
    }

    private function testConflictCheckDetectsOverlap(): void
    {
        $employeeId = Uuid::v7();
        $existing = Reservation::createForCustomer(
            id: Uuid::v7(),
            reservationDate: new \DateTimeImmutable('2030-01-10 10:00:00'),
            serviceId: Uuid::v7(),
            customerId: Uuid::v7(),
            employeeId: $employeeId,
            servicePrice: 100.0,
            serviceDuration: 60.0,
        );

        $repository = $this->buildConflictRepository([$existing]);

        self::assertTrue(
            $repository->employeeHasReservationConflict(
                employeeId: $employeeId,
                reservationDate: new \DateTimeImmutable('2030-01-10 10:30:00'),
                serviceDuration: 30.0,
            ),
            'Overlapping reservation should be treated as conflict',
        );
    }

    private function testCanceledReservationDoesNotBlockConflictCheck(): void
    {
        $employeeId = Uuid::v7();
        $existing = Reservation::createForCustomer(
            id: Uuid::v7(),
            reservationDate: new \DateTimeImmutable('2030-01-10 10:00:00'),
            serviceId: Uuid::v7(),
            customerId: Uuid::v7(),
            employeeId: $employeeId,
            servicePrice: 100.0,
            serviceDuration: 60.0,
        );
        $existing->cancel();

        $repository = $this->buildConflictRepository([$existing]);

        self::assertFalse(
            $repository->employeeHasReservationConflict(
                employeeId: $employeeId,
                reservationDate: new \DateTimeImmutable('2030-01-10 10:30:00'),
                serviceDuration: 30.0,
            ),
            'Canceled reservation should not block new reservation',
        );
    }

    private function testCreateReservationAutoAssignsAvailableEmployee(): void
    {
        $service = $this->createService();
        $customer = $this->createCustomer('customer@example.com');
        $employee = $this->createEmployee($service->getCompany(), $service->getCompanyAddress(), 'employee@example.com');
        $service->addEmployee($employee);

        $serviceRepository = new InMemoryServiceRepository([$service]);
        $customerRepository = new InMemoryCustomerRepository([$customer]);
        $employeeRepository = new InMemoryEmployeeRepository([$employee]);
        $reservationRepository = new CapturingReservationRepository();
        $checker = new class($employee) extends ReservationAvailabilityChecker {
            public function __construct(private readonly ?Employee $selectedEmployee)
            {
            }

            public function findAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): ?Employee
            {
                return $this->selectedEmployee;
            }

            public function hasAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): bool
            {
                return null !== $this->selectedEmployee;
            }

            public function isEmployeeAvailableForService(Service $service, Employee $employee, \DateTimeImmutable $reservationDate): bool
            {
                return true;
            }
        };

        $handler = new CreateReservationHandler(
            serviceRepository: $serviceRepository,
            customerRepository: $customerRepository,
            employeeRepository: $employeeRepository,
            reservationRepository: $reservationRepository,
            reservationAvailabilityChecker: $checker,
            reservationFactory: new ReservationFactory(),
            logger: new NullLogger(),
        );

        $handler(new CreateReservationCommand(
            createReservationDTO: new CreateReservationDTO(
                serviceId: $service->getId()->toString(),
                customerId: $customer->getUuid()->toString(),
                reservationDate: '2030-02-01 12:00:00',
            ),
            id: Uuid::v7(),
        ));

        self::assertNotNull($reservationRepository->savedReservation, 'Reservation should be saved');
        self::assertSame(
            $employee->getUuid()->toString(),
            $reservationRepository->savedReservation?->getEmployeeId()?->toString(),
            'Handler should auto-assign available employee',
        );
    }

    private function testAcceptReservationPermissions(): void
    {
        $service = $this->createService();
        $reservation = Reservation::createForCustomer(
            id: Uuid::v7(),
            reservationDate: new \DateTimeImmutable('2030-03-01 09:00:00'),
            serviceId: $service->getId(),
            customerId: Uuid::v7(),
            employeeId: null,
            servicePrice: 50.0,
            serviceDuration: 30.0,
        );

        $tenant = $this->createTenant([$service->getCompany()]);
        $validator = new AcceptReservationValidator(
            reservationRepository: new CapturingReservationRepository([$reservation]),
            serviceRepository: new InMemoryServiceRepository([$service]),
            security: $this->securityForUser($tenant),
        );

        $validator(new AcceptReservationCommand($reservation->getId()));

        $otherCompany = $this->createCompany('Other Company');
        $otherAddress = $this->createCompanyAddress($otherCompany);
        $outsideEmployee = $this->createEmployee($otherCompany, $otherAddress, 'other-employee@example.com');
        $forbiddenValidator = new AcceptReservationValidator(
            reservationRepository: new CapturingReservationRepository([$reservation]),
            serviceRepository: new InMemoryServiceRepository([$service]),
            security: $this->securityForUser($outsideEmployee),
        );

        self::assertThrows(
            static fn () => $forbiddenValidator(new AcceptReservationCommand($reservation->getId())),
            ValidationFail::class,
            'Employee from another company/location should not accept reservation',
        );
    }

    private function testCancelReservationPermissions(): void
    {
        $service = $this->createService();
        $customer = $this->createCustomer('owner@example.com');
        $reservation = Reservation::createForCustomer(
            id: Uuid::v7(),
            reservationDate: new \DateTimeImmutable('2030-03-01 09:00:00'),
            serviceId: $service->getId(),
            customerId: $customer->getUuid(),
            employeeId: null,
            servicePrice: 50.0,
            serviceDuration: 30.0,
        );

        $allowedValidator = new CancelReservationValidator(
            reservationRepository: new CapturingReservationRepository([$reservation]),
            serviceRepository: new InMemoryServiceRepository([$service]),
            security: $this->securityForUser($customer),
        );

        $allowedValidator(new CancelReservationCommand($reservation->getId()));

        $foreignCustomer = $this->createCustomer('foreign@example.com');
        $forbiddenValidator = new CancelReservationValidator(
            reservationRepository: new CapturingReservationRepository([$reservation]),
            serviceRepository: new InMemoryServiceRepository([$service]),
            security: $this->securityForUser($foreignCustomer),
        );

        self::assertThrows(
            static fn () => $forbiddenValidator(new CancelReservationCommand($reservation->getId())),
            ValidationFail::class,
            'Customer should not cancel foreign reservation',
        );
    }

    private function testGuestReservationStoresGuestDataAndCancellationToken(): void
    {
        $service = $this->createService(duration: 90.0, price: 220.0);
        $employee = $this->createEmployee($service->getCompany(), $service->getCompanyAddress(), 'employee@example.com');
        $service->addEmployee($employee);

        $serviceRepository = new InMemoryServiceRepository([$service]);
        $employeeRepository = new InMemoryEmployeeRepository([$employee]);
        $reservationRepository = new CapturingReservationRepository();
        $checker = new class($employee) extends ReservationAvailabilityChecker {
            public function __construct(private readonly ?Employee $selectedEmployee)
            {
            }

            public function findAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): ?Employee
            {
                return $this->selectedEmployee;
            }

            public function hasAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): bool
            {
                return null !== $this->selectedEmployee;
            }

            public function isEmployeeAvailableForService(Service $service, Employee $employee, \DateTimeImmutable $reservationDate): bool
            {
                return true;
            }
        };

        $handler = new CreateGuestReservationHandler(
            serviceRepository: $serviceRepository,
            employeeRepository: $employeeRepository,
            reservationRepository: $reservationRepository,
            reservationAvailabilityChecker: $checker,
            reservationFactory: new ReservationFactory(),
            commandBus: new CapturingMessageBus(),
            logger: new NullLogger(),
        );

        $handler(new CreateGuestReservationCommand(
            createGuestReservationDTO: new CreateGuestReservationDTO(
                serviceId: $service->getId()->toString(),
                reservationDate: '2030-04-01 14:00:00',
                firstname: ' Guest ',
                lastname: ' Person ',
                email: 'guest@example.com ',
                phone: ' 123456789 ',
            ),
            id: Uuid::v7(),
            guestCancellationToken: 'guest-cancel-token',
        ));

        self::assertNotNull($reservationRepository->savedReservation, 'Guest reservation should be saved');
        self::assertSame('Guest', $reservationRepository->savedReservation?->getGuestFirstname());
        self::assertSame('Person', $reservationRepository->savedReservation?->getGuestLastname());
        self::assertSame('guest@example.com', $reservationRepository->savedReservation?->getGuestEmail());
        self::assertSame('123456789', $reservationRepository->savedReservation?->getGuestPhone());
        self::assertSame('guest-cancel-token', $reservationRepository->savedReservation?->getGuestCancellationToken());
        self::assertSame(220.0, $reservationRepository->savedReservation?->getServicePrice());
        self::assertSame(90.0, $reservationRepository->savedReservation?->getServiceDuration());
    }

    private function testGuestReservationDispatchesCancellationLinkCommand(): void
    {
        $service = $this->createService();
        $employee = $this->createEmployee($service->getCompany(), $service->getCompanyAddress(), 'employee@example.com');
        $service->addEmployee($employee);

        $serviceRepository = new InMemoryServiceRepository([$service]);
        $employeeRepository = new InMemoryEmployeeRepository([$employee]);
        $reservationRepository = new CapturingReservationRepository();
        $messageBus = new CapturingMessageBus();
        $checker = new class($employee) extends ReservationAvailabilityChecker {
            public function __construct(private readonly ?Employee $selectedEmployee)
            {
            }

            public function findAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): ?Employee
            {
                return $this->selectedEmployee;
            }

            public function hasAvailableEmployee(Service $service, \DateTimeImmutable $reservationDate): bool
            {
                return null !== $this->selectedEmployee;
            }

            public function isEmployeeAvailableForService(Service $service, Employee $employee, \DateTimeImmutable $reservationDate): bool
            {
                return true;
            }
        };

        $handler = new CreateGuestReservationHandler(
            serviceRepository: $serviceRepository,
            employeeRepository: $employeeRepository,
            reservationRepository: $reservationRepository,
            reservationAvailabilityChecker: $checker,
            reservationFactory: new ReservationFactory(),
            commandBus: $messageBus,
            logger: new NullLogger(),
        );

        $reservationId = Uuid::v7();

        $handler(new CreateGuestReservationCommand(
            createGuestReservationDTO: new CreateGuestReservationDTO(
                serviceId: $service->getId()->toString(),
                reservationDate: '2030-04-02 14:00:00',
                firstname: 'Guest',
                lastname: 'Person',
                email: 'guest@example.com',
                phone: '123456789',
            ),
            id: $reservationId,
            guestCancellationToken: 'guest-cancel-token',
        ));

        self::assertSame(1, \count($messageBus->dispatchedMessages), 'Guest reservation should dispatch one mailer command');
        self::assertTrue($messageBus->dispatchedMessages[0] instanceof SendGuestCancellationLinkCommand, 'Dispatched message should be guest cancellation mail command');
        self::assertSame($reservationId->toString(), $messageBus->dispatchedMessages[0]->reservationId);
    }

    private function testGuestReservationResponseDoesNotExposeCancellationToken(): void
    {
        $commandBus = new CapturingMessageBus();
        $queryBus = new CapturingMessageBus();
        $controller = new \App\Reservation\Presentation\Controller\ReservationController($commandBus, $queryBus);

        $response = $controller->createGuestReservationAction(
            new CreateGuestReservationDTO(
                serviceId: Uuid::v7()->toString(),
                reservationDate: '2030-05-01 12:00:00',
                firstname: 'Guest',
                lastname: 'Person',
                email: 'guest@example.com',
                phone: '123456789',
            )
        );

        self::assertTrue($response instanceof JsonResponse, 'Controller should return JSON response');
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertArrayHasKey('id', $payload, 'Guest reservation response should return id');
        self::assertArrayNotHasKey('guestCancellationToken', $payload, 'Guest reservation response must not expose guest cancellation token');
    }

    private function createCompany(string $displayName = 'Test Company'): Company
    {
        $company = new Company(
            displayName: $displayName,
            legalName: $displayName . ' LLC',
            taxId: '1234567890',
            currency: 'PLN',
        );
        $company->setId(Uuid::v7());

        return $company;
    }

    private function createCompanyAddress(Company $company): CompanyAddress
    {
        $address = new CompanyAddress(
            street: 'Street',
            city: 'City',
            country: 'PL',
            postalCode: '00-001',
            apartmentNo: 1,
            buildingNo: 2,
            name: 'HQ',
        );
        $address->setCompany($company);
        $this->setPrivateProperty($address, 'id', Uuid::v7());

        return $address;
    }

    private function createService(
        float $duration = 30.0,
        float $price = 100.0,
        ?Company $company = null,
        ?CompanyAddress $companyAddress = null,
    ): Service {
        $company ??= $this->createCompany();
        $companyAddress ??= $this->createCompanyAddress($company);

        $service = new Service(
            name: 'Haircut',
            description: 'Test service',
            duration: $duration,
            price: $price,
            company: $company,
            companyAddress: $companyAddress,
        );
        $service->setId(Uuid::v7());

        return $service;
    }

    private function createCustomer(string $email): Customer
    {
        $customer = new Customer(
            email: $email,
            password: 'secret',
            metadata: $this->createMetadata(),
            firstname: 'John',
            lastname: 'Doe',
            isActive: true,
            phone: '123456789',
        );
        $customer->setUuid(Uuid::v7());

        return $customer;
    }

    private function createEmployee(Company $company, CompanyAddress $address, string $email): Employee
    {
        $employee = new Employee(
            email: $email,
            password: 'secret',
            metadata: $this->createMetadata(),
            company: $company,
            companyAddress: $address,
            firstname: 'Emp',
            lastname: 'Loyee',
            isActive: true,
        );
        $employee->setUuid(Uuid::v7());

        return $employee;
    }

    /**
     * @param Company[] $companies
     */
    private function createTenant(array $companies): Tenant
    {
        $tenant = new Tenant(
            email: 'tenant@example.com',
            password: 'secret',
            metadata: $this->createMetadata(),
            isActive: true,
            firstname: 'Tenant',
            lastname: 'Owner',
        );
        $tenant->setUuid(Uuid::v7());

        foreach ($companies as $company) {
            $tenant->addCompany($company);
        }

        return $tenant;
    }

    private function createMetadata(): UserMetadata
    {
        return new UserMetadata(
            activationToken: Uuid::v7()->toString(),
            activationExpiresAt: new \DateTimeImmutable('+2 hours'),
        );
    }

    /**
     * @param Reservation[] $reservations
     */
    private function buildConflictRepository(array $reservations): ReservationRepository
    {
        $reflection = new \ReflectionClass(ReservationRepository::class);
        /** @var ReservationRepository $repository */
        $repository = $reflection->newInstanceWithoutConstructor();

        $entityRepository = new class($reservations) extends EntityRepository {
            /**
             * @param Reservation[] $reservations
             */
            public function __construct(private readonly array $reservations)
            {
            }

            public function findBy(array $criteria, array|null $orderBy = null, int|null $limit = null, int|null $offset = null): array
            {
                return $this->reservations;
            }
        };

        $this->setPrivateProperty($repository, 'repository', $entityRepository);

        return $repository;
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

    private static function assertTrue(bool $condition, string $message = 'Expected true'): void
    {
        if (!$condition) {
            throw new \RuntimeException($message);
        }
    }

    private static function assertFalse(bool $condition, string $message = 'Expected false'): void
    {
        self::assertTrue(!$condition, $message);
    }

    private static function assertSame(mixed $expected, mixed $actual, string $message = 'Values are not identical'): void
    {
        if ($expected !== $actual) {
            throw new \RuntimeException(sprintf('%s. Expected %s, got %s', $message, var_export($expected, true), var_export($actual, true)));
        }
    }

    private static function assertNotNull(mixed $value, string $message = 'Value should not be null'): void
    {
        if (null === $value) {
            throw new \RuntimeException($message);
        }
    }

    private static function assertArrayHasKey(string|int $key, array $array, string $message = 'Array key not found'): void
    {
        if (!\array_key_exists($key, $array)) {
            throw new \RuntimeException(sprintf('%s. Missing key: %s', $message, (string) $key));
        }
    }

    private static function assertArrayNotHasKey(string|int $key, array $array, string $message = 'Array key should not exist'): void
    {
        if (\array_key_exists($key, $array)) {
            throw new \RuntimeException(sprintf('%s. Unexpected key: %s', $message, (string) $key));
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

final class InMemoryServiceRepository implements ServiceRepositoryInterface
{
    /**
     * @param Service[] $services
     */
    public function __construct(private array $services)
    {
    }

    public function save(Service $service): void
    {
        $this->services[$service->getId()->toString()] = $service;
    }

    public function findById(Uuid $id): ?Service
    {
        foreach ($this->services as $service) {
            if ($service->getId()->equals($id)) {
                return $service;
            }
        }

        return null;
    }

    public function findByFilters(?Uuid $companyId, ?Uuid $companyAddressId, bool $onlyActive = true): array
    {
        return array_values(array_filter(
            $this->services,
            static function (Service $service) use ($companyId, $companyAddressId, $onlyActive): bool {
                if (null !== $companyId && !$service->getCompany()->getId()->equals($companyId)) {
                    return false;
                }

                if (null !== $companyAddressId && !$service->getCompanyAddress()->getId()->equals($companyAddressId)) {
                    return false;
                }

                if ($onlyActive && !$service->isActive()) {
                    return false;
                }

                return true;
            },
        ));
    }

    public function findByIds(array $ids): array
    {
        return array_values(array_filter(
            $this->services,
            static fn (Service $service): bool => array_any($ids, static fn (Uuid $id): bool => $service->getId()->equals($id)),
        ));
    }
}

final class InMemoryCustomerRepository implements CustomerRepositoryInterface
{
    /**
     * @param Customer[] $customers
     */
    public function __construct(private array $customers)
    {
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
        return array_values(array_filter(
            $this->customers,
            static fn (Customer $customer): bool => array_any($ids, static fn (Uuid $id): bool => $customer->getUuid()->equals($id)),
        ));
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
        unset($this->customers[$customer->getUuid()->toString()]);
    }

    public function findByToken(string $token): ?Customer
    {
        return null;
    }
}

final class InMemoryEmployeeRepository implements EmployeeRepositoryInterface
{
    /**
     * @param Employee[] $employees
     */
    public function __construct(private array $employees)
    {
    }

    public function findByEmail(string $email): ?Employee
    {
        foreach ($this->employees as $employee) {
            if ($employee->getEmail() === $email) {
                return $employee;
            }
        }

        return null;
    }

    public function findById(Uuid $uuid): ?Employee
    {
        foreach ($this->employees as $employee) {
            if ($employee->getUuid()->equals($uuid)) {
                return $employee;
            }
        }

        return null;
    }

    public function findByIds(array $uuids): array
    {
        return array_values(array_filter(
            $this->employees,
            static fn (Employee $employee): bool => array_any($uuids, static fn (Uuid $id): bool => $employee->getUuid()->equals($id)),
        ));
    }

    public function save(Employee $employee): void
    {
        $this->employees[$employee->getUuid()->toString()] = $employee;
    }

    public function lock(Uuid $uuid): void
    {
    }

    public function remove(Employee $Employee): void
    {
        unset($this->employees[$Employee->getUuid()->toString()]);
    }

    public function findByToken(string $token): ?Employee
    {
        return null;
    }
}

final class CapturingReservationRepository implements ReservationRepositoryInterface
{
    public ?Reservation $savedReservation = null;

    /**
     * @param Reservation[] $reservations
     */
    public function __construct(private array $reservations = [])
    {
    }

    public function findById(Uuid $id): ?Reservation
    {
        foreach ($this->reservations as $reservation) {
            if ($reservation->getId()->equals($id)) {
                return $reservation;
            }
        }

        return $this->savedReservation?->getId()->equals($id) ? $this->savedReservation : null;
    }

    public function findByGuestCancellationToken(string $guestCancellationToken): ?Reservation
    {
        foreach (array_merge($this->reservations, null !== $this->savedReservation ? [$this->savedReservation] : []) as $reservation) {
            if ($reservation->getGuestCancellationToken() === $guestCancellationToken) {
                return $reservation;
            }
        }

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
        $this->savedReservation = $reservation;
        $this->reservations[$reservation->getId()->toString()] = $reservation;
    }
}

final class CapturingMessageBus implements MessageBusInterface
{
    public array $dispatchedMessages = [];

    public function dispatch(object $message, array $stamps = []): Envelope
    {
        $this->dispatchedMessages[] = $message;

        return new Envelope($message, $stamps);
    }
}
