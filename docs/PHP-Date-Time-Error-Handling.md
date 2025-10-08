## Table of exception / error classes & related APIs

### Exception / Error class hierarchy

These are the classes introduced (or available) via the Date/Time extension for error handling. ([PHP][1])

* `DateError` (extends `\Error`) ([PHP][2])

  * `DateObjectError` (extends `DateError`) ([PHP][3])
  * `DateRangeError` (extends `DateError`) ([PHP][4])
* `DateException` (extends `\Exception`) ([PHP][5])

  * `DateInvalidTimeZoneException` (extends `DateException`) ([PHP][6])
  * `DateInvalidOperationException` (extends `DateException`) ([PHP][7])
  * `DateMalformedStringException` (extends `DateException`) ([PHP.Watch][8])
  * `DateMalformedIntervalStringException` (extends `DateException`) ([PHP.Watch][8])
  * `DateMalformedPeriodStringException` (extends `DateException`) ([PHP.Watch][8])

---

## New / relevant DateTime / DateTimeImmutable APIs in PHP 8.4

These are methods added in PHP 8.4 relevant to date/time handling that may throw the above exceptions/errors. ([PHP][9])

* `DateTime::createFromTimestamp()`
* `DateTime::getMicrosecond()`
* `DateTime::setMicrosecond(int $microsecond)`
* `DateTimeImmutable::createFromTimestamp()`
* `DateTimeImmutable::getMicrosecond()`
* `DateTimeImmutable::setMicrosecond(int $microsecond)`

---

## Detailed documentation

Below is a more detailed breakdown of the exception/error classes and the method APIs (summary, return types, parameters) as available in PHP 8.4 (or inherited from earlier). Where PHP’s own manual does not provide a custom method signature beyond general Exception / Error, I note inherited behavior.

### Exception / Error classes

```php
class DateError extends \Error
```

| Feature             | Description                                                                                                                                                          |
| ------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Summary             | Base error type for fatal/internal date/time errors (timezone DB corrupt, internal runtime issues) ([PHP][2])                                                        |
| Constructor         | `public Error::__construct(string $message = "", int $code = 0, ?Throwable $previous = null)` (inherited) ([PHP][2])                                                 |
| Methods (inherited) | `getMessage(): string`, `getCode(): int`, `getFile(): string`, `getLine(): int`, `getTrace(): array`, `getPrevious(): ?Throwable`, `__toString(): string` ([PHP][2]) |

```php
class DateObjectError extends DateError
```

| Feature                         | Description                                                                                                           |
| ------------------------------- | --------------------------------------------------------------------------------------------------------------------- |
| Summary                         | Thrown when a Date/Time object wasn’t initialized properly (e.g. subclass didn’t call parent constructor). ([PHP][3]) |
| Inherited constructor / methods | Same as `DateError` / `Error`                                                                                         |

```php
class DateRangeError extends DateError
```

| Feature                         | Description                                                                                                                     |
| ------------------------------- | ------------------------------------------------------------------------------------------------------------------------------- |
| Summary                         | Thrown when a date/time operation produces a value outside the allowed range (e.g. timestamp not fitting in integer) ([PHP][4]) |
| Inherited constructor / methods | Same as `DateError` / `Error`                                                                                                   |

```php
class DateException extends \Exception
```

| Feature           | Description                                                                                                                                               |
| ----------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Summary           | Base exception for invalid user input in date/time context (malformed strings, invalid operations) ([PHP][5])                                             |
| Constructor       | `public Exception::__construct(string $message = "", int $code = 0, ?Throwable $previous = null)` (inherited) ([PHP][5])                                  |
| Inherited methods | `getMessage(): string`, `getCode(): int`, `getFile(): string`, `getLine(): int`, `getPrevious(): ?Throwable`, `getTrace(): array`, `__toString(): string` |

```php
class DateInvalidTimeZoneException extends DateException
```

| Feature                         | Description                                                                      |
| ------------------------------- | -------------------------------------------------------------------------------- |
| Summary                         | Thrown when constructing a `DateTimeZone` with invalid name or offset ([PHP][6]) |
| Inherited constructor / methods | As inherited from `DateException`                                                |

```php
class DateInvalidOperationException extends DateException
```

| Feature                         | Description                                                                                                                       |
| ------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Summary                         | Thrown when performing an unsupported operation on a Date/Time object (e.g. subtracting a “special relative” interval) ([PHP][7]) |
| Inherited constructor / methods | As inherited from `DateException`                                                                                                 |

```php
class DateMalformedStringException extends DateException
```

| Feature                         | Description                                                                                                                        |
| ------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------- |
| Summary                         | Thrown when a date/time string cannot be parsed in `DateTime` / `DateTimeImmutable` constructor or parse function ([PHP.Watch][8]) |
| Inherited constructor / methods | As inherited from `DateException`                                                                                                  |

```php
class DateMalformedIntervalStringException extends DateException
```

| Feature                         | Description                                                                                       |
| ------------------------------- | ------------------------------------------------------------------------------------------------- |
| Summary                         | Thrown when an interval string (in `DateInterval`) is malformed or not parseable ([PHP.Watch][8]) |
| Inherited constructor / methods | As inherited from `DateException`                                                                 |

```php
class DateMalformedPeriodStringException extends DateException
```

| Feature                         | Description                                                                                    |
| ------------------------------- | ---------------------------------------------------------------------------------------------- |
| Summary                         | Thrown when a `DatePeriod` is instantiated with a bad/malformed period string ([PHP.Watch][8]) |
| Inherited constructor / methods | As inherited from `DateException`                                                              |

---

### New DateTime / DateTimeImmutable methods in PHP 8.4

These methods were newly added in PHP 8.4 to ease microsecond and timestamp handling. ([PHP][10])

```php
public static function DateTime::createFromTimestamp(int $timestamp, ?DateTimeZone $timezone = null): ?DateTime
```

* Summary: Creates a `DateTime` object from a Unix timestamp (seconds since epoch).
* Return type: `?DateTime`
* Parameters:
    - `int $timestamp` — the Unix timestamp (seconds)
    - `?DateTimeZone $timezone` — optional timezone object; if omitted, uses default timezone
* Throws: May throw `DateRangeError` if timestamp out of range on some platforms. (Inherited behavior)

```php
public function DateTime::getMicrosecond(): int
```

* Summary: Returns the microsecond component (0–999,999) of the `DateTime` object.
* Return type: `int`
* Parameters: none
* Throws: If internal state invalid, may propagate `DateError` subclasses (not explicitly documented)

```php
public function DateTime::setMicrosecond(int $microsecond): void
```

* Summary: Sets the microsecond part of this `DateTime`.
* Return type: `void`
* Parameters:
    - `int $microsecond` — must be between 0 and 999,999 inclusive ([M.academy][11])
* Throws: `DateRangeError` if the supplied microsecond is out of bounds ([M.academy][11])

```php
public static function DateTimeImmutable::createFromTimestamp(int $timestamp, ?DateTimeZone $timezone = null): ?DateTimeImmutable
```

* Summary: Same as `DateTime::createFromTimestamp` but returns an immutable object
* Return type: `?DateTimeImmutable`
* Parameters:
    - `int $timestamp`
    - `?DateTimeZone $timezone`
* Throws: Same potential errors as `DateTime::createFromTimestamp`

```php
public function DateTimeImmutable::getMicrosecond(): int
```

* Summary: Returns the microsecond portion (0–999,999) of the immutable date/time object
* Return type: `int`
* Parameters: none
* Throws: Not explicitly documented

```php
public function DateTimeImmutable::setMicrosecond(int $microsecond): DateTimeImmutable
```

* Summary: Returns a new `DateTimeImmutable` with the microsecond part set
* Return type: `DateTimeImmutable`
* Parameters:
    - `int $microsecond` (0 to 999,999)
* Throws: `DateRangeError` if out of bounds

---