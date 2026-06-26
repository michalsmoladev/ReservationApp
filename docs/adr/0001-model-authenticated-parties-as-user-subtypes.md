# Model authenticated parties as User subtypes

ReservationApp models authenticated people as subtypes of a shared `User`: tenant, employee, and customer. This centralizes authentication, activation, profile fields, and security integration, while accepting the coupling and migration cost that come with a single inheritance hierarchy for different participant types.
