<?php

namespace App\Exceptions;

use DateMalformedStringException;
use Throwable;

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
