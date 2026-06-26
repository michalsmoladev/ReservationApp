# ReservationApp

ReservationApp is a booking context for services offered by people or companies. It describes the parties, offers, and bookings involved in scheduling a service.

## Language

**User**:
An authenticated person in the system. A user is always one of the supported participant types, such as a tenant, employee, or customer.
_Avoid_: Account

**Tenant**:
A company owner who has administrator permissions for one or more companies.
_Avoid_: SaaS tenant, provider

**Company**:
A business profile under which services are offered, including public and legal identity such as name, tax identifier, currency, and one or more locations.
_Avoid_: Firm, organization, provider

**Location**:
A place where a company provides services. A company may have multiple locations.
_Avoid_: Address, branch

**Employee**:
A user account that performs or manages services for exactly one company and one location. If the same real person works for two companies, they are represented by two separate employee accounts.
_Avoid_: Staff user, worker

**Customer**:
A person who books services. A customer may have a user account or may book as a guest using only contact details.
_Avoid_: Client, buyer

**Guest Customer**:
A customer without a user account who makes a reservation using first name, last name, phone, and email. Guest reservations remain unlinked until the customer registers, then guest reservations with the same email are assigned to the new customer account automatically.
_Avoid_: Anonymous user

**Service**:
An offer owned by a company and assigned to a specific location, with a name, optional description, duration, and price. A service also defines which employees are allowed to perform it.
_Avoid_: Product, treatment, appointment type

**Reservation**:
A booking made by a customer for a service at a chosen date and time. A customer may choose a specific eligible employee or reserve without choosing one, in which case the system assigns an available eligible employee. A reservation keeps the service price and duration from the moment it was made.
_Avoid_: Appointment, booking

**Reservation Status**:
The state of a reservation. A new reservation waits for acceptance by the company owner or any employee of the company before it becomes confirmed.
_Avoid_: Booking state

**Cancellation**:
A change that cancels a reservation. Customers may cancel no later than 24 hours before the reservation time; the company owner or employees may cancel at any time.
_Avoid_: Resignation

**Calendar**:
The schedule used to manage reservations and available times for companies and employees.
_Avoid_: Timetable

**Availability**:
The set of times when a service can be reserved, calculated from both company opening hours and employee working time or absences.
_Avoid_: Free time, slots

**Price**:
The amount charged for a service, shared by all employees assigned to that service. Reservations keep a price snapshot from the moment of booking.
_Avoid_: Cost, fee

**Duration**:
The planned length of a service, shared by all employees assigned to that service. Reservations keep a duration snapshot from the moment of booking.
_Avoid_: Length

**Contact**:
A person's or company's reachable details. Guest reservations require first name, last name, phone, and email.
_Avoid_: Details
