<?php

namespace App\Exceptions;

use DateRangeError;
use Throwable;

class CalendarRangeException extends DateRangeError
{
    public function __construct(
        string $message = '',
        string $calendarSystem = 'unknown',
        ?array $dateComponents = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $context = $calendarSystem !== 'unknown' ? "[{$calendarSystem}] " : '';
        parent::__construct($context.$message, $code, $previous);
    }
}
