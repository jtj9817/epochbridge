<?php

declare(strict_types=1);

use App\Models\JapaneseEra;
use App\Services\AncientMacedonianService;
use App\Services\AssyrianCalendarService;
use App\Services\BabylonianCalendarService;
use App\Services\ChineseCalendarService;
use App\Services\CopticCalendarService;
use App\Services\EgyptianCalendarService;
use App\Services\EthiopianCalendarService;
use App\Services\FrenchRepublicanService;
use App\Services\HebrewCalendarService;
use App\Services\IslamicCalendarService;
use App\Services\JapaneseCalendarService;
use App\Services\JulianCalendarService;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2024, 6, 15, 12, 0, 0));
});

afterEach(function () {
    Carbon::setTestNow();
});

it('returns today date for gregorian input type', function () {
    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'today',
        'input_type' => 'gregorian',
    ]));

    $response->assertSuccessful()
        ->assertJsonPath('date', '2024-06-15')
        ->assertJsonPath('input_type', 'gregorian');
});

it('returns yesterday date for gregorian input type', function () {
    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'yesterday',
        'input_type' => 'gregorian',
    ]));

    $response->assertSuccessful()
        ->assertJsonPath('date', '2024-06-14')
        ->assertJsonPath('input_type', 'gregorian');
});

it('returns tomorrow date for gregorian input type', function () {
    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'tomorrow',
        'input_type' => 'gregorian',
    ]));

    $response->assertSuccessful()
        ->assertJsonPath('date', '2024-06-16')
        ->assertJsonPath('input_type', 'gregorian');
});

it('returns unix timestamp for unix input type', function () {
    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'today',
        'input_type' => 'unix',
    ]));

    $response->assertSuccessful()
        ->assertJsonPath('input_type', 'unix');

    $date = $response->json('date');
    expect($date)->toBeNumeric();
});

it('returns jalali date for jalali input type', function () {
    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'today',
        'input_type' => 'jalali',
    ]));

    $response->assertSuccessful()
        ->assertJsonPath('input_type', 'jalali');

    // Jalali date should have format YYYY/MM/DD
    $date = $response->json('date');
    expect($date)->toMatch('/^\d{4}\/\d{2}\/\d{2}$/');
});

it('returns iso8601 format for iso8601 input type', function () {
    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'today',
        'input_type' => 'iso8601',
    ]));

    $response->assertSuccessful()
        ->assertJsonPath('input_type', 'iso8601');

    $date = $response->json('date');
    expect($date)->toContain('2024-06-15T');
});

it('returns julian day number for jd input type', function () {
    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'today',
        'input_type' => 'jd',
    ]));

    $response->assertSuccessful()
        ->assertJsonPath('input_type', 'jd');

    $date = $response->json('date');
    expect($date)->toBeNumeric();
});

it('returns the formatted date for each calendar input type', function (string $inputType) {
    if ($inputType === 'japanese') {
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
    }

    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'today',
        'input_type' => $inputType,
    ]));

    $now = now();

    $services = [
        'hebrew' => HebrewCalendarService::class,
        'islamic' => IslamicCalendarService::class,
        'french_republican' => FrenchRepublicanService::class,
        'chinese' => ChineseCalendarService::class,
        'japanese' => JapaneseCalendarService::class,
        'coptic' => CopticCalendarService::class,
        'egyptian' => EgyptianCalendarService::class,
        'ethiopian' => EthiopianCalendarService::class,
        'babylonian' => BabylonianCalendarService::class,
        'assyrian' => AssyrianCalendarService::class,
        'macedonian' => AncientMacedonianService::class,
        'julian' => JulianCalendarService::class,
    ];

    $expected = match ($inputType) {
        'mjd' => (string) (gregoriantojd($now->month, $now->day, $now->year) - 2400000.5),
        'mayan' => $now->format('Y/m/d'),
        default => app($services[$inputType])->fromGregorian($now)['full_date'],
    };

    $response->assertSuccessful()
        ->assertJsonPath('date', $expected)
        ->assertJsonPath('input_type', $inputType);
})->with([
    'mjd',
    'hebrew',
    'islamic',
    'french_republican',
    'chinese',
    'japanese',
    'coptic',
    'egyptian',
    'ethiopian',
    'babylonian',
    'assyrian',
    'macedonian',
    'julian',
    'mayan',
]);

it('validates action parameter', function () {
    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'invalid',
        'input_type' => 'gregorian',
    ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['action']);
});

it('validates input_type parameter', function () {
    $response = $this->getJson(route('frontend.quick-action.show', [
        'action' => 'today',
        'input_type' => 'invalid',
    ]));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_type']);
});

it('requires both action and input_type', function () {
    $response = $this->getJson(route('frontend.quick-action.show'));

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['action', 'input_type']);
});
