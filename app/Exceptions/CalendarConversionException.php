<?php

namespace App\Exceptions;

use DateException;
use DateMalformedStringException;
use DateRangeError;
use Throwable;

abstract class CalendarConversionException extends DateException
{
    public function __construct(
        string $message = "",
        string $calendarSystem = 'unknown',
        ?array $dateComponents = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $context = $calendarSystem !== 'unknown' ? "[{$calendarSystem}] " : '';
        parent::__construct($context . $message, $code, $previous);
    }
}

class CalendarMalformedDateException extends DateMalformedStringException
{
    public function __construct(
        string $message = "",
        string $calendarSystem = 'unknown',
        ?array $dateComponents = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $context = $calendarSystem !== 'unknown' ? "[{$calendarSystem}] " : '';
        parent::__construct($context . $message, $code, $previous);
    }
}

class CalendarRangeException extends DateRangeError
{
    public function __construct(
        string $message = "",
        string $calendarSystem = 'unknown',
        ?array $dateComponents = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $context = $calendarSystem !== 'unknown' ? "[{$calendarSystem}] " : '';
        parent::__construct($context . $message, $code, $previous);
    }
}

class CalendarCalculationException extends CalendarConversionException
{
    // For astronomical calculation failures, algorithm errors, etc.
}

class CalendarValidationException extends CalendarConversionException
{
    // For date component validation failures
}
