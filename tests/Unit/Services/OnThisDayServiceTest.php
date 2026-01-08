<?php

use App\Models\OnThisDayEntry;
use App\Services\Wikimedia\OnThisDayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('returns cached day info without calling the API', function () {
    $payload = [
        'selected' => [],
        'events' => [],
        'births' => [],
        'deaths' => [],
        'holidays' => [],
    ];

    OnThisDayEntry::create([
        'language' => 'en',
        'type' => 'all',
        'month' => 1,
        'day' => 7,
        'day_info' => json_encode($payload),
        'fetched_at' => now(),
    ]);

    Http::fake();

    $service = new OnThisDayService;
    $result = $service->getForDate('2024-01-07');

    expect($result['cached'])->toBeTrue()
        ->and($result['error'])->toBeNull()
        ->and($result['data'])->toBeArray()
        ->and($result['data'])->toHaveKey('events');

    Http::assertNothingSent();
});

it('calls the API and stores results when cache is missing', function () {
    config()->set('services.wikimedia.token', 'test-token');
    config()->set('services.wikimedia.user_agent', 'EpochBridge Test');

    $payload = [
        'selected' => [],
        'events' => [
            ['year' => 1980, 'text' => 'Example event', 'pages' => []],
        ],
        'births' => [],
        'deaths' => [],
        'holidays' => [],
    ];

    Http::fake([
        'https://api.wikimedia.org/feed/v1/wikipedia/en/onthisday/all/01/07' => Http::response($payload, 200),
    ]);

    $service = new OnThisDayService;
    $result = $service->getForDate('2024-01-07');

    expect($result['cached'])->toBeFalse()
        ->and($result['error'])->toBeNull()
        ->and($result['data'])->toHaveKey('events');

    $this->assertDatabaseHas('file', [
        'language' => 'en',
        'type' => 'all',
        'month' => 1,
        'day' => 7,
    ]);
});

it('handles API failures gracefully', function () {
    config()->set('services.wikimedia.token', 'test-token');
    config()->set('services.wikimedia.user_agent', 'EpochBridge Test');

    Http::fake([
        'https://api.wikimedia.org/feed/v1/wikipedia/en/onthisday/all/01/07' => Http::response([], 500),
    ]);

    $service = new OnThisDayService;
    $result = $service->getForDate('2024-01-07');

    expect($result['cached'])->toBeFalse()
        ->and($result['data'])->toBeNull()
        ->and($result['error'])->toBeString();

    $this->assertDatabaseCount('file', 0);
});
