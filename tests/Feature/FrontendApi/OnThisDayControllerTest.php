<?php

declare(strict_types=1);

use App\Models\OnThisDayEntry;

it('returns on-this-day data for a valid date', function () {
    $dayInfo = [
        'selected' => [
            ['year' => 2020, 'text' => 'Test selected event', 'pages' => []],
        ],
        'events' => [
            ['year' => 1900, 'text' => 'Test event', 'pages' => []],
        ],
        'births' => [],
        'deaths' => [],
        'holidays' => [],
    ];

    OnThisDayEntry::query()->create([
        'language' => 'en',
        'type' => 'all',
        'month' => 1,
        'day' => 15,
        'day_info' => json_encode($dayInfo),
        'fetched_at' => now(),
    ]);

    $response = $this->getJson(route('frontend.on-this-day.show', ['date' => '2024-01-15']));

    $response->assertSuccessful()
        ->assertJsonPath('date', '2024-01-15')
        ->assertJsonPath('cached', true)
        ->assertJsonPath('data.selected.0.text', 'Test selected event')
        ->assertJsonPath('data.events.0.year', 1900);
});

it('returns empty data when no cache entry exists', function () {
    $response = $this->getJson(route('frontend.on-this-day.show', ['date' => '2024-06-30']));

    $response->assertSuccessful()
        ->assertJsonPath('date', '2024-06-30')
        ->assertJsonPath('cached', false)
        ->assertJsonPath('data', []);
});

it('validates the date parameter is required', function () {
    $response = $this->getJson(route('frontend.on-this-day.show'));

    $response->assertUnprocessable();
});

it('validates the date format', function () {
    $response = $this->getJson(route('frontend.on-this-day.show', ['date' => 'not-a-date']));

    $response->assertUnprocessable();
});

it('validates the type parameter', function () {
    $response = $this->getJson(route('frontend.on-this-day.show', [
        'date' => '2024-01-15',
        'type' => 'invalid',
    ]));

    $response->assertUnprocessable();
});

it('accepts valid type parameters', function (string $type) {
    $response = $this->getJson(route('frontend.on-this-day.show', [
        'date' => '2024-01-15',
        'type' => $type,
    ]));

    $response->assertSuccessful();
})->with(['all', 'events', 'births', 'deaths', 'holidays']);

it('returns error when cached data is invalid JSON', function () {
    OnThisDayEntry::query()->create([
        'language' => 'en',
        'type' => 'all',
        'month' => 3,
        'day' => 10,
        'day_info' => '{invalid json}',
        'fetched_at' => now(),
    ]);

    $response = $this->getJson(route('frontend.on-this-day.show', ['date' => '2024-03-10']));

    $response->assertSuccessful()
        ->assertJsonPath('cached', true)
        ->assertJsonPath('error', 'On This Day data is unavailable right now.');
});

it('returns empty data when cache entry has empty day_info', function () {
    OnThisDayEntry::query()->create([
        'language' => 'en',
        'type' => 'all',
        'month' => 5,
        'day' => 20,
        'day_info' => '',
        'fetched_at' => now(),
    ]);

    $response = $this->getJson(route('frontend.on-this-day.show', ['date' => '2024-05-20']));

    $response->assertSuccessful()
        ->assertJsonPath('cached', true)
        ->assertJsonPath('data', []);
});
