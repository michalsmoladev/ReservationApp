# ReservationApp Project Skill

Use this guide whenever working in this repository. ReservationApp is a Symfony/PHP backend for booking services offered by companies and employees. The current codebase already includes service reservation flows, availability calendars, and read APIs for reservations.

## Project Shape

- Backend: Symfony 7.2, PHP >= 8.4.
- Persistence: Doctrine ORM with attribute mappings.
- Auth: Symfony Security plus Lexik JWT on `/api/auth`.
- Architecture: feature modules under `src/`, mostly split into `Domain`, `Application`, `Infrastructure`, and `Presentation`.
- Domain language: read `CONTEXT.md` before renaming concepts or reshaping the business model.
- Main modules:
  - `User`: tenants, employees, customers, activation.
  - `Company`: companies and company addresses.
  - `Reservation`: services, reservations, availability calendars, availability query.
  - `Core`: Messenger middleware, validation/locking attributes, kernel exception handling.
  - `Mailer`: activation message command flow.

## Local Commands

- Run Symfony commands with `php bin/console ...`.
- Cheap validation commands that currently work:
  - `php tests/run.php`
  - `php bin/console lint:container`
  - `php bin/console doctrine:schema:validate --skip-sync`
  - targeted syntax checks with `php -l <file>`
- Docker services are defined in `docker-compose.yaml`.
- There is still no first-class PHPUnit harness in the repo. The current executable regression suite is the custom runner in `tests/run.php`.

## Architecture Rules

- Keep feature code inside its module:
  - `Domain/Entity` for Doctrine entities and repository interfaces
  - `Application/Command/<UseCase>` for commands, handlers, validators, DTOs
  - `Application/Query/<UseCase>` for read handlers and DTOs
  - `Application/Factory` for entity construction
  - `Infrastructure` for Doctrine-backed repositories
  - `Presentation/Controller` for HTTP controllers
- Controllers should stay thin: validate route params, map payload/query params, dispatch messages, return JSON.
- Use Symfony Messenger for application flow. Handlers are marked with `#[AsMessageHandler]`.
- Use repository interfaces from `Domain` in handlers/services.
- Prefer UUID v7 for externally created identifiers.

## Message Bus Conventions

The command bus is customized in `config/packages/messenger.yaml`.

- Every dispatched command/query must have a validator service named by replacing the final `Command` or `Query` suffix with `Validator`.
- Validator classes must be callable with `__invoke(...)`.
- Validator classes must have `#[AsMessageValidator]`.
- Missing validators are runtime errors in `ValidationMiddleware`.
- Lockers are optional. If used, name them by replacing `Command`/`Query` with `Locker` and mark with `#[AsMessageLocker]`.
- `Kernel.php` autoconfigures `#[AsMessageValidator]` as `message.validator` and `#[AsMessageLocker]` as `message.locker`.

## Domain Notes

- `User` is Doctrine single-table inheritance:
  - `tenant` -> `Tenant`
  - `employee` -> `Employee`
  - `customer` -> `Customer`
- Users have `uuid`, `email`, `password`, `roles`, `firstname`, `lastname`, `metadata`, timestamps, and `isActive`.
- Password hashing is handled by `UserPasswordHasListener`; do not hash passwords manually in handlers.
- `KernelListener` blocks authenticated API users whose account is not active, except paths listed in `app.exclude_path`.
- `Tenant` has many-to-many companies via `tenant_company`.
- `Employee` belongs to a company and company address, and can be assigned to many services.
- `Service` belongs to company + company address and snapshots `price` and `duration` into reservations.
- `Reservation` stores:
  - `reservationDate`
  - `status`
  - `serviceId`
  - optional `customerId`
  - optional `employeeId`
  - snapshot `servicePrice`
  - snapshot `serviceDuration`
  - optional note
  - optional guest contact data
  - optional `guestCancellationToken`
- Availability calendar entities currently exist:
  - `CompanyOpeningHour`
  - `EmployeeWorkingHour`
  - `EmployeeAbsence`

## Reservation Flows

### Reservation create

- `POST /api/reservation`
- Explicit customer flow.
- Validates UUIDs, future date, employee/service relationship.
- Enforces reservation availability through `ReservationAvailabilityChecker`:
  - company opening hours
  - employee working hours
  - employee absences
  - overlapping active reservations
- If `employeeId` is missing, the system auto-assigns an available employee from the service.
- Reservation snapshots service price and duration at creation time.

### Guest reservation create

- `POST /api/reservation/guest`
- Stores guest firstname, lastname, email, phone.
- Uses the same calendar-aware availability checks as regular reservation create.
- Auto-assigns employee when `employeeId` is missing.
- Returns:
  - reservation `id`
  - `guestCancellationToken`

### Reservation status flow

- `POST /api/reservation/{id}/accept`
- `POST /api/reservation/{id}/cancel`
- Access rules:
  - tenant only for own company
  - employee only for own company + location
  - customer only for own reservations in cancel flow
  - guest is handled separately, outside authenticated cancel flow

### Guest cancellation

- `POST /api/reservation/guest/cancel/{token}`
- Token-based public cancel flow for guest reservations.
- Guest cancellation is allowed only if:
  - reservation exists
  - reservation is still guest-owned (`customerId = null`)
  - reservation is not already canceled
  - reservation date is at least 24 hours in the future

### Reservation read API

- `GET /api/reservation/{id}`
- `GET /api/reservations?companyId=&employeeId=&customerId=&from=&to=&status=`
- Query layer returns enriched reservation DTOs with service/company/employee/customer/guest data.
- Visibility rules:
  - tenant sees only own companies
  - employee sees only own company + location
  - customer sees only own reservations
- Inaccessible reservation detail returns 404 instead of exposing foreign data.

### Availability calendars write API

- `POST /api/company-opening-hour`
- `POST /api/employee-working-hour`
- `POST /api/employee-absence`
- Includes ownership and duplicate checks in validators.

### Availability API

- `GET /api/service/{id}/availability?from=&to=`
- Current availability calculation intersects:
  - company opening hours
  - employee working hours
- Then subtracts:
  - employee absences
  - active reservations
- Returns slots grouped with available `employeeIds`.

## Current HTTP/API State

- Attribute-based routes are imported for:
  - `src/User/Presentation/Controller`
  - `src/Company/Presentation/Controller`
  - `src/Reservation/Presentation/Controller`
- Current reservation-related endpoints:
  - `POST /api/service`
  - `GET /api/service/{id}/availability`
  - `POST /api/reservation`
  - `POST /api/reservation/guest`
  - `POST /api/reservation/{id}/accept`
  - `POST /api/reservation/{id}/cancel`
  - `POST /api/reservation/guest/cancel/{token}`
  - `GET /api/reservation/{id}`
  - `GET /api/reservations`
  - `POST /api/company-opening-hour`
  - `POST /api/employee-working-hour`
  - `POST /api/employee-absence`

## Current Sharp Edges

These are the sharp edges that still appear to be real as of July 21, 2026:

- `EmployeeController::activeEmployeeAction()` still uses route path `api/employee/activate/{token}` without a leading `/`.
- `TenantRepository::findByToken()` still searches plain `token`, while activation data for users is stored in `UserMetadata`. Tenant activation flow likely remains inconsistent with employee/customer activation.
- `ActivateTenantValidator` still treats tenant activation token as UUID-like (`toString()` usage), unlike the now string-based customer/employee token lookups.
- `GetCustomerByIdHandler` has an empty `if (!$customer) {}` branch and does not throw a not-found error.
- `POST /api/reservation/guest` and `POST /api/reservation/guest/cancel/{token}` are excluded from `KernelListener`, but they are still not listed as `PUBLIC_ACCESS` in `config/packages/security.yaml`. In practice, Symfony access control may still block unauthenticated guest flows.
- `CreateGuestReservation` currently returns `guestCancellationToken` directly from API because there is no finished mailer/delivery flow for cancellation links.
- The regression suite lives in `tests/run.php`, not in a standard PHPUnit bootstrap yet.

## Implementation Guidance

- Follow existing naming and placement before introducing new abstractions.
- When adding a use case, wire command/query, handler, validator, DTO, repository support, and controller together.
- When adding Doctrine fields/entities, update mapping and add a migration.
- Keep JSON errors compatible with `KernelListener`:
  - not-found style errors should extend `NamedException`
  - validation failures should throw `ValidationFail`
- Preserve `declare(strict_types=1);` where files already use it.
- Before changing docs again, prefer verifying claims against:
  - `config/routes.yaml`
  - `config/packages/security.yaml`
  - `config/services.yaml`
  - current controllers under `src/Reservation/Presentation/Controller`
