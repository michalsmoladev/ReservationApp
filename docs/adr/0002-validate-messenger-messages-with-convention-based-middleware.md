# Validate Messenger messages with convention-based middleware

ReservationApp validates commands and queries through custom Messenger middleware that derives a matching validator from the message class name. This enforces a consistent validation step before handlers run, while accepting boilerplate and runtime failures when a message is dispatched without the expected validator.
