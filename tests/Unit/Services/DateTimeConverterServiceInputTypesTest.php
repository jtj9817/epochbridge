<?php

use App\Models\JapaneseEra;
use App\Services\AssyrianCalendarService;
use App\Services\BabylonianCalendarService;
use App\Services\ChineseCalendarService;
use App\Services\CopticCalendarService;
use App\Services\DateTimeConverterService;
use App\Services\EgyptianCalendarService;
use App\Services\EthiopianCalendarService;
use App\Services\FrenchRepublicanService;
use App\Services\HebrewCalendarService;
use App\Services\IslamicCalendarService;
use App\Services\JulianCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // Ensure at least one era exists for Japanese conversions.
    JapaneseEra::firstOrCreate(
        ['name_romanized' => 'Reiwa'],
        [
            'name_japanese' => '令和',
            'name_english' => 'Reiwa',
            'start_date' => '2019-05-01',
            'end_date' => null,
            'historical_context' => 'Current era',
        ]
    );
});

it('parses dates for all supported input types without throwing', function (string $inputType, bool $assertExactGregorian) {
    $service = app(DateTimeConverterService::class);

    $base = Carbon::create(2024, 9, 1, 0, 0, 0, 'UTC');
    $expectedGregorian = $base->format('Y-m-d');

    $dateInput = match ($inputType) {
        'gregorian' => $base->format('Y-m-d'),
        'jalali' => Jalalian::fromCarbon($base)->format('Y/m/d'),
        'unix' => (string) $base->timestamp,
        'iso8601' => $base->toIso8601String(),
        'jd' => (string) gregoriantojd((int) $base->month, (int) $base->day, (int) $base->year),
        'mjd' => (string) (gregoriantojd((int) $base->month, (int) $base->day, (int) $base->year) - 2400000.5),
        'hebrew' => app(HebrewCalendarService::class)->fromGregorian($base)['full_date'],
        'islamic' => app(IslamicCalendarService::class)->fromGregorian($base)['full_date'],
        'french_republican' => app(FrenchRepublicanService::class)->fromGregorian($base)['full_date'],
        'japanese' => app(\App\Services\JapaneseCalendarService::class)->fromGregorian($base)['full_date'],
        'coptic' => app(CopticCalendarService::class)->fromGregorian($base)['full_date'],
        'egyptian' => app(EgyptianCalendarService::class)->fromGregorian($base)['full_date'],
        'ethiopian' => app(EthiopianCalendarService::class)->fromGregorian($base)['full_date'],
        'assyrian' => app(AssyrianCalendarService::class)->fromGregorian($base)['full_date'],
        'julian' => app(JulianCalendarService::class)->fromGregorian($base)['full_date'],
        // Mayan is entered as Gregorian with slashes.
        'mayan' => $base->format('Y/m/d'),
        // Approximations.
        'babylonian' => app(BabylonianCalendarService::class)->fromGregorian($base)['full_date'],
        'macedonian' => app(\App\Services\AncientMacedonianService::class)->fromGregorian($base)['full_date'],
        'chinese' => app(ChineseCalendarService::class)->fromGregorian($base)['full_date'],
        default => throw new InvalidArgumentException("Unsupported input type: {$inputType}"),
    };

    $ref = new \ReflectionClass($service);
    $method = $ref->getMethod('parseInputDate');
    $method->setAccessible(true);

    /** @var \Illuminate\Support\Carbon $parsed */
    $parsed = $method->invoke($service, $dateInput, $inputType);

    expect($parsed)->toBeInstanceOf(Carbon::class);

    if ($assertExactGregorian) {
        expect($parsed->format('Y-m-d'))->toBe($expectedGregorian);
    }
})->with([
    'gregorian' => ['gregorian', true],
    'jalali' => ['jalali', true],
    'unix' => ['unix', true],
    'iso8601' => ['iso8601', true],
    'jd' => ['jd', true],
    'mjd' => ['mjd', true],
    'hebrew' => ['hebrew', true],
    'islamic' => ['islamic', true],
    'french_republican' => ['french_republican', true],
    'japanese' => ['japanese', true],
    'coptic' => ['coptic', true],
    'egyptian' => ['egyptian', true],
    'ethiopian' => ['ethiopian', true],
    'assyrian' => ['assyrian', true],
    'julian' => ['julian', true],
    'mayan' => ['mayan', true],
    'babylonian' => ['babylonian', false],
    'macedonian' => ['macedonian', false],
    'chinese' => ['chinese', true],
]);

it('accepts chinese leap-month notation produced by the service', function () {
    $converter = app(DateTimeConverterService::class);
    $calendar = app(ChineseCalendarService::class);

    $start = Carbon::create(2020, 1, 1, 0, 0, 0, 'Asia/Shanghai');
    $found = null;

    for ($i = 0; $i < 800; $i++) {
        $date = $start->copy()->addDays($i);
        $full = $calendar->fromGregorian($date)['full_date'];

        if (str_contains($full, '闰')) {
            $found = [$date, $full];
            break;
        }
    }

    expect($found)->not->toBeNull();
    [$date, $full] = $found;

    $result = $converter->convert($full, 'chinese');

    expect($result['gregorian']['full_date'])->toBe($date->format('Y-m-d'));
});
