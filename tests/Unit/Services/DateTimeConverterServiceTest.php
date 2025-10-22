<?php

use App\Models\JapaneseEra;
use App\Services\DateTimeConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

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

it('parses japanese era input into the correct gregorian date', function () {
    seedTestJapaneseEras();

    $service = app(DateTimeConverterService::class);

    $result = $service->convert('Reiwa 5/06/15', 'japanese');

    expect($result['gregorian']['full_date'])->toBe('2023-06-15');
});

it('rejects malformed japanese era input', function () {
    seedTestJapaneseEras();

    $service = app(DateTimeConverterService::class);

    $service->convert('Reiwa5-06-15', 'japanese');
})->throws(\App\Exceptions\CalendarMalformedDateException::class, 'Invalid date format for japanese. Use "<Era Name> <Year>/<Month>/<Day>".');

function seedTestJapaneseEras(): void
{
    JapaneseEra::create([
        'name_japanese' => '平成',
        'name_romanized' => 'Heisei',
        'name_english' => 'Heisei',
        'start_date' => '1989-01-08',
        'end_date' => '2019-04-30',
        'historical_context' => null,
    ]);

    JapaneseEra::create([
        'name_japanese' => '令和',
        'name_romanized' => 'Reiwa',
        'name_english' => 'Reiwa',
        'start_date' => '2019-05-01',
        'end_date' => null,
        'historical_context' => null,
    ]);
}
