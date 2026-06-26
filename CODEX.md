# ReservationApp Project Skill

Use this guide whenever working in this repository. ReservationApp is a Symfony/PHP backend for booking services offered by people or companies. The intended product includes reservation calendars, services, prices, and contact/company data.

## Project Shape

- Backend: Symfony 7.2, PHP >= 8.4.
- Persistence: Doctrine ORM with attribute mappings, MySQL in Docker.
- Auth: Symfony Security plus Lexik JWT on `/api/auth`.
- Architecture: feature modules under `src/`, mostly split into `Domain`, `Application`, `Infrastructure`, and `Presentation`.
- Domain language: read `CONTEXT.md` before naming or reshaping business concepts.
- Architectural decisions: read `docs/adr/` before changing user modeling or Messenger validation conventions.
- Main modules:
  - `User`: tenants, employees, customers, activation, auth-related user state.
  - `Company`: company creation and company address work in progress.
  - `Reservation`: service/reservation domain started, controller mostly stubbed.
  - `Core`: message bus middleware, validation/locking attributes, exception handling.
  - `Mailer`: activation message command exists.

## Local Commands

- Install/update dependencies with Composer.
- Run Symfony commands through `php bin/console ...` or the PHP container when the local PHP version is not 8.4-compatible.
- Docker services are defined in `docker-compose.yaml`: `php`, `nginx`, `mysql`, `rabbitmq`, and `redis`.
- There is no project test suite visible yet. Prefer at least `php bin/console lint:container` and targeted PHP syntax checks after changes when possible.

## Architecture Rules

- Keep feature code inside its module, following the existing directory layout:
  - `Domain/Entity` for Doctrine entities and repository interfaces.
  - `Application/Command/<UseCase>` for commands, handlers, validators, and DTOs.
  - `Application/Query/<UseCase>` for read queries and DTO responses.
  - `Application/Factory` for entity construction.
  - `Infrastructure` for Doctrine-backed repositories.
  - `Presentation/Controller` for HTTP controllers.
- Controllers should stay thin: map request DTOs, validate route UUIDs, dispatch command/query messages, and return JSON.
- Use Symfony Messenger for application flow. Handlers are marked with `#[AsMessageHandler]`.
- Use repository interfaces from the domain in handlers/services; implement them in `Infrastructure`.
- Use factories to build aggregates/entities from DTOs rather than spreading construction logic through handlers.
- Prefer UUID v7 for externally created identifiers, matching existing controller/factory style.

## Message Bus Conventions

The command bus is customized in `config/packages/messenger.yaml`.

- Every dispatched command/query must have a validator service named by replacing the final `Command` or `Query` suffix with `Validator`.
  - Example: `CreateTenantCommand` requires `CreateTenantValidator`.
  - The validator class must be callable with `__invoke(...)`.
  - The validator class must have `#[AsMessageValidator]`.
- Missing validators are runtime errors in `ValidationMiddleware`.
- Lockers are optional. If used, name them by replacing `Command`/`Query` with `Locker` and mark with `#[AsMessageLocker]`.
- `Kernel.php` autoconfigures `#[AsMessageValidator]` as `message.validator` and `#[AsMessageLocker]` as `message.locker`.
- `CommandStackingMiddleware` adds `DispatchAfterCurrentBusStamp` automatically.

## Domain Notes

- `User` is a Doctrine single-table inheritance root with discriminator values:
  - `tenant` -> `Tenant`
  - `employee` -> `Employee`
  - `customer` -> `Customer`
- Users have `uuid`, `email`, `password`, `roles`, `firstname`, `lastname`, `metadata`, timestamps, and `isActive`.
- Password hashing is handled by `UserPasswordHasListener` on Doctrine `prePersist` and `preUpdate`; do not hash passwords manually in handlers.
- Activation state matters: `KernelListener` blocks authenticated API users whose account is not active, except configured public paths in `app.exclude_path`.
- `Tenant` is intended to own or relate to companies via `tenant_company`.
- `Employee` has many-to-many `JobRole`.
- `Customer` has optional `phone`.
- `Company` currently contains display/legal name, tax ID, currency, timestamps, and address relation work in progress.
- `Reservation\Domain\Entity\Service` currently contains name, description, duration, price, and timestamps.

## HTTP/API State

- Routes are attribute-based.
- `config/routes.yaml` currently imports:
  - `src/User/Presentation/Controller`
  - `src/Company/Presentation/Controller`
- `Reservation` controllers are not imported in `config/routes.yaml` yet.
- Public/create/activate paths are configured in `config/services.yaml` and `config/packages/security.yaml`.
- Existing endpoints include:
  - `POST /api/tenant/create`
  - `GET|PATCH|DELETE /api/tenant/{id}`
  - `POST /api/employee/create`
  - `GET|PATCH|DELETE /api/employee/{id}`
  - `GET api/employee/activate/{token}` currently lacks a leading slash in the route attribute.
  - `POST /api/customer/create`
  - `GET|PATCH|DELETE /api/customer/{id}`
  - `GET api/customer/activate/{token}` currently lacks a leading slash in the route attribute.
  - `POST /api/company`
  - `POST /api/service` exists in code but is not imported by routes yet.

## Current Sharp Edges

Treat these as current project state, not necessarily intended design:

- `CompanyController::createCompanyAction()` contains `dd($this->getUser())`, so company creation will stop before dispatching.
- `CustomerController::activeCustomerAction()` dispatches `ActivateEmployeeCommand` and appears to be missing/importing the customer activation command.
- Some route attributes for activation paths omit the leading `/`.
- `CompanyFactory::create()` ignores `$companyDTO->id` and generates a new UUID itself.
- `CompanyFactory::addAddresses()` is private and currently not called.
- `Company` imports `CompanyAddress`, but its relation is declared as `ManyToMany(targetEntity: Address::class)` from the user module; `addAddress()` accepts `CompanyAddress`. Resolve this before building company address behavior.
- `Tenant::$companies` is not initialized in the constructor.
- `Company::$addresses` is not initialized in the constructor.
- Some repositories remove entities without flushing.
- `TenantRepository::findByToken()` searches `token`, but activation data appears to live in `UserMetadata`.
- `Reservation\Domain\Entity\Service` uses `#[ORM\GeneratedValue]` with a UUID property; verify Doctrine UUID generation before relying on it.
- Doctrine mapping in `config/packages/doctrine.yaml` currently includes `User` and `Company`, not `Reservation`.

## Implementation Guidance

- Follow existing naming and placement before introducing new abstractions.
- When adding a use case, create command/query, handler, validator, DTO if needed, and route/controller wiring together.
- When adding Doctrine entities in a new module, update Doctrine mappings and create a migration.
- Keep JSON errors compatible with `KernelListener`: domain/application not-found style errors should extend `NamedException`; validation errors should throw `ValidationFail`.
- Preserve strict typing style where files already use `declare(strict_types=1);`.
- Be careful with user-owned uncommitted changes. This repository currently has active edits in `config/packages/doctrine.yaml`, `config/routes.yaml`, `src/Reservation/Presentation/Controller/ServiceController.php`, `src/User/Domain/Entity/Address.php`, `src/User/Domain/Entity/Tenant/Tenant.php`, and untracked `src/Company/`.
