<?php

use App\Services\DateTimeConverterService;
use Illuminate\Support\Facades\Cache;;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Cache::flush();
});

it('returns comprehensive conversion payload for a valid gregorian date', function () {
    $service = app(DateTimeConverterService::class);

    $result = $service->convert('2024-01-15');

    expect($result)->toHaveKeys([
        'gregorian',
        'jalali',
        'unix',
        'iso8601',
        'julian-day',
        'modified-julian-day',
    ]);

    expect($result['gregorian']['full_date'])->toBe('2024-01-15');
    expect($result['jalali']['full_date'])->toBe('1402/10/25');
    expect($result['unix'])->toBe(1705276800);
    expect($result['iso8601'])->toBe('2024-01-15T00:00:00+00:00');
});

it('caches repeated conversions for identical inputs', function () {
    $service = app(DateTimeConverterService::class);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturnUsing(function ($key, $ttl, $callback) {
            expect($ttl)->toBe(3600);

            return $callback();
        });

    $service->convert('2024-01-15');
});

it('throws an exception for an invalid date string', function () {
    $service = app(DateTimeConverterService::class);

    $service->convert('not-a-valid-date');
})->throws(InvalidArgumentException::class, 'Invalid date format provided for the selected input type.');

it('throws an exception for an empty date string', function () {
    $service = app(DateTimeConverterService::class);

    $service->convert('');
})->throws(InvalidArgumentException::class, 'Input date cannot be empty.');
