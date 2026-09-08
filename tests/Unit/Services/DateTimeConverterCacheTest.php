<?php

use App\Services\DateTimeConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Cache::spy();

    $this->service = app(DateTimeConverterService::class);
});

it('does not cache conversions for time-precise inputs', function (string $inputType, string $date) {
    $this->service->convert($date, $inputType);

    Cache::shouldNotHaveReceived('remember');
})->with([
    'unix' => ['unix', '1705276800'],
    'iso8601' => ['iso8601', '2024-01-15T00:00:00Z'],
]);

it('does not cache additional conversions for time-precise inputs', function () {
    $this->service->convertAdditional('1705276800', 'unix');

    Cache::shouldNotHaveReceived('remember');
});

it('caches date-only conversions under the fast key', function () {
    $this->service->convert('2024-01-15', 'gregorian');

    Cache::shouldHaveReceived('remember')
        ->once()
        ->withArgs(fn (string $key) => str_starts_with($key, 'conversion_fast_'));
});

it('caches additional date-only conversions under the additional key', function () {
    $this->service->convertAdditional('2024-01-15', 'gregorian');

    Cache::shouldHaveReceived('remember')
        ->once()
        ->withArgs(fn (string $key) => str_starts_with($key, 'conversion_additional_'));
});
