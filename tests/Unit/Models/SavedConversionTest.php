<?php

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('saved conversion model casts conversion_data as array', function () {
    $user = User::factory()->create();
    $conversionData = [
        'gregorian' => [
            'full_date' => '2024-01-15',
            'name' => 'Gregorian',
            'tags' => ['modern', 'solar'],
        ],
        'jalali' => [
            'full_date' => '1402/10/24',
            'name' => 'Jalali',
            'tags' => ['modern', 'solar'],
        ],
        'unix' => 1705276800,
        'iso8601' => '2024-01-15T00:00:00Z',
    ];

    $savedConversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => $conversionData,
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/24',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00Z',
    ]);

    expect($savedConversion->conversion_data)->toBeArray()
        ->and($savedConversion->conversion_data['gregorian']['full_date'])->toBe('2024-01-15')
        ->and($savedConversion->conversion_data['jalali']['full_date'])->toBe('1402/10/24')
        ->and($savedConversion->conversion_data['unix'])->toBe(1705276800);
});

test('backward compatibility accessors work with conversion_data', function () {
    $user = User::factory()->create();
    $conversionData = [
        'gregorian' => [
            'full_date' => '2024-01-15',
            'name' => 'Gregorian',
            'tags' => ['modern', 'solar'],
        ],
        'jalali' => [
            'full_date' => '1402/10/24',
            'name' => 'Jalali',
            'tags' => ['modern', 'solar'],
        ],
        'unix' => 1705276800,
        'iso8601' => '2024-01-15T00:00:00Z',
    ];

    $savedConversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => $conversionData,
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/24',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00Z',
    ]);

    // Test accessors return data from conversion_data
    expect($savedConversion->gregorian_date)->toBe('2024-01-15')
        ->and($savedConversion->jalali_date)->toBe('1402/10/24')
        ->and($savedConversion->unix_timestamp)->toBe(1705276800)
        ->and($savedConversion->iso8601_string)->toBe('2024-01-15T00:00:00Z');
});

test('backward compatibility accessors fall back to original fields', function () {
    $user = User::factory()->create();

    // Create conversion without conversion_data (old format)
    $savedConversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/24',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00Z',
    ]);

    // Test accessors fall back to original fields
    expect($savedConversion->gregorian_date)->toBe('2024-01-15')
        ->and($savedConversion->jalali_date)->toBe('1402/10/24')
        ->and($savedConversion->unix_timestamp)->toBe(1705276800)
        ->and($savedConversion->iso8601_string)->toBe('2024-01-15T00:00:00Z');
});

test('saved conversion handles missing conversion_data gracefully', function () {
    $user = User::factory()->create();

    $savedConversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/24',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00Z',
    ]);

    expect($savedConversion->conversion_data)->toBeNull();
});

test('saved conversion belongs to user', function () {
    $user = User::factory()->create();
    $savedConversion = SavedConversion::factory()->create(['user_id' => $user->id]);

    expect($savedConversion->user)->toBeInstanceOf(User::class)
        ->and($savedConversion->user->id)->toBe($user->id);
});

test('saved conversion is mass assignable', function () {
    $user = User::factory()->create();
    $conversionData = ['test' => 'data'];

    $savedConversion = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'conversion_data' => $conversionData,
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/24',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00Z',
    ]);

    expect($savedConversion->original_input)->toBe('2024-01-15')
        ->and($savedConversion->conversion_data)->toBe($conversionData);
});

test('saved conversion casts gregorian_date as date', function () {
    $user = User::factory()->create();
    $savedConversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'gregorian_date' => '2024-01-15',
    ]);

    expect($savedConversion->gregorian_date)->toBeInstanceOf(\Carbon\Carbon::class)
        ->and($savedConversion->gregorian_date->format('Y-m-d'))->toBe('2024-01-15');

    $withConversionData = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2024-01-20',
        'conversion_data' => [
            'gregorian' => ['full_date' => '2024-01-20'],
        ],
        'gregorian_date' => '2024-01-20',
    ]);

    expect($withConversionData->gregorian_date)->toBe('2024-01-20');
});

test('getPaginatedForUser hydrates conversion data and legacy fields', function () {
    $user = User::factory()->create();

    $withJson = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    $legacy = SavedConversion::create([
        'user_id' => $user->id,
        'original_input' => '2023-05-10',
        'gregorian_date' => '2023-05-10',
        'jalali_date' => '1402/02/20',
        'unix_timestamp' => 1683676800,
        'iso8601_string' => '2023-05-10T00:00:00Z',
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $paginator = SavedConversion::getPaginatedForUser($user->id, 10);
    $items = collect($paginator->items());

    $jsonItem = $items->firstWhere('id', $withJson->id);
    $legacyItem = $items->firstWhere('id', $legacy->id);

    expect($jsonItem)->not()->toBeNull()
        ->and($jsonItem->conversion_data)->toBeArray()
        ->and($jsonItem->gregorian_date)->toBe($withJson->conversion_data['gregorian']['full_date']);

    expect($legacyItem)->not()->toBeNull()
        ->and($legacyItem->conversion_data)->toBeNull()
        ->and($legacyItem->gregorian_date)->toBe('2023-05-10')
        ->and($legacyItem->jalali_date)->toBe('1402/02/20')
        ->and($legacyItem->unix_timestamp)->toBe(1683676800)
        ->and($legacyItem->iso8601_string)->toBe('2023-05-10T00:00:00Z');
});
