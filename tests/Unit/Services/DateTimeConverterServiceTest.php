<?php

namespace Tests\Unit\Services;

use App\Services\DateTimeConverterService;
use InvalidArgumentException;

it('correctly converts a valid date string', function () {
    $service = new DateTimeConverterService();
    $dateString = '2024-01-15';

    $expected = [
        'gregorian' => '2024-01-15',
        'jalali'    => '1402-10-25',
        'unix'      => 1705276800,
        'iso8601'   => '2024-01-15T00:00:00+00:00',
    ];

    $result = $service->convert($dateString);

    expect($result)->toBe($expected);
});

it('throws an exception for an invalid date string', function () {
    $service = new DateTimeConverterService();
    $dateString = 'not-a-valid-date';

    $service->convert($dateString);
})->throws(InvalidArgumentException::class, 'Invalid date format provided.');

it('throws an exception for an unparsable date string', function () {
    $service = new DateTimeConverterService();
    $dateString = '2024-99-99'; // Invalid month/day

    $service->convert($dateString);
})->throws(InvalidArgumentException::class, 'Invalid date format provided.');
