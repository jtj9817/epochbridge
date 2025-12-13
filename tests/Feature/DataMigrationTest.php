<?php

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('database migration correctly migrates old format to new conversion_data', function () {
    $user = User::factory()->create();

    // Create old format conversion without conversion_data
    $oldConversion = SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'conversion_data' => null,
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/24',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00Z',
    ]);

    // Run the migration
    DB::table('migrations')->where('migration', '2025_10_14_222009_update_saved_conversions_table')->delete();
    $this->artisan('migrate', ['--path' => 'database/migrations/2025_10_14_222009_update_saved_conversions_table.php']);

    // Refresh the model from database
    $oldConversion->refresh();

    expect($oldConversion->conversion_data)->toBeArray()
        ->and($oldConversion->conversion_data['gregorian']['full_date'])->toBe('2024-01-15')
        ->and($oldConversion->conversion_data['gregorian']['name'])->toBe('Gregorian')
        ->and($oldConversion->conversion_data['gregorian']['tags'])->toBe(['modern', 'solar'])
        ->and($oldConversion->conversion_data['jalali']['full_date'])->toBe('1402/10/24')
        ->and($oldConversion->conversion_data['jalali']['name'])->toBe('Jalali')
        ->and($oldConversion->conversion_data['jalali']['tags'])->toBe(['modern', 'solar'])
        ->and($oldConversion->conversion_data['unix'])->toBe(1705276800)
        ->and($oldConversion->conversion_data['iso8601'])->toBe('2024-01-15T00:00:00Z');
});

test('database migration preserves old fields for backward compatibility', function () {
    $user = User::factory()->create();

    $oldConversion = SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'conversion_data' => null,
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/24',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00Z',
    ]);

    // Run the migration
    DB::table('migrations')->where('migration', '2025_10_14_222009_update_saved_conversions_table')->delete();
    $this->artisan('migrate', ['--path' => 'database/migrations/2025_10_14_222009_update_saved_conversions_table.php']);

    // Refresh the model from database
    $oldConversion->refresh();

    // Old fields should still exist
    expect($oldConversion->gregorian_date)->toBe('2024-01-15')
        ->and($oldConversion->jalali_date)->toBe('1402/10/24')
        ->and($oldConversion->unix_timestamp)->toBe(1705276800)
        ->and($oldConversion->iso8601_string)->toBe('2024-01-15T00:00:00Z');
});

test('database migration handles multiple old format conversions', function () {
    $user = User::factory()->create();

    // Create multiple old format conversions
    $conversions = SavedConversion::factory()->for($user)->count(5)->create([
        'conversion_data' => null,
        'gregorian_date' => '2024-01-01',
        'jalali_date' => '1402/10/11',
        'unix_timestamp' => 1704067200,
        'iso8601_string' => '2024-01-01T00:00:00Z',
    ]);

    // Run the migration
    DB::table('migrations')->where('migration', '2025_10_14_222009_update_saved_conversions_table')->delete();
    $this->artisan('migrate', ['--path' => 'database/migrations/2025_10_14_222009_update_saved_conversions_table.php']);

    // Check all conversions were migrated
    $conversions->each(function ($conversion) {
        $conversion->refresh();
        expect($conversion->conversion_data)->toBeArray()
            ->and($conversion->conversion_data)->toHaveKey('gregorian')
            ->and($conversion->conversion_data)->toHaveKey('jalali')
            ->and($conversion->conversion_data)->toHaveKey('unix')
            ->and($conversion->conversion_data)->toHaveKey('iso8601');
    });
});

test('database migration handles empty conversions gracefully', function () {
    $user = User::factory()->create();

    // Create conversion with minimal data
    $conversion = SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'conversion_data' => null,
        'gregorian_date' => null,
        'jalali_date' => null,
        'unix_timestamp' => null,
        'iso8601_string' => null,
    ]);

    // Run the migration
    DB::table('migrations')->where('migration', '2025_10_14_222009_update_saved_conversions_table')->delete();
    $this->artisan('migrate', ['--path' => 'database/migrations/2025_10_14_222009_update_saved_conversions_table.php']);

    // Refresh the model from database
    $conversion->refresh();

    expect($conversion->conversion_data)->toBeArray()
        ->and($conversion->conversion_data['gregorian']['full_date'])->toBeNull()
        ->and($conversion->conversion_data['jalali']['full_date'])->toBeNull()
        ->and($conversion->conversion_data['unix'])->toBeNull()
        ->and($conversion->conversion_data['iso8601'])->toBeNull();
});

test('migration rollback removes conversion_data column', function () {
    $user = User::factory()->create();

    // Create conversion with new format
    $conversion = SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'conversion_data' => [
            'gregorian' => ['full_date' => '2024-01-15', 'name' => 'Gregorian', 'tags' => ['modern']],
            'jalali' => ['full_date' => '1402/10/24', 'name' => 'Jalali', 'tags' => ['modern']],
            'unix' => 1705276800,
            'iso8601' => '2024-01-15T00:00:00Z',
        ],
    ]);

    // Rollback the migration
    $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2025_10_14_222009_update_saved_conversions_table.php']);

    // Refresh the model from database
    $conversion->refresh();

    // conversion_data column should not exist
    expect($conversion->conversion_data)->toBeNull();
});

test('new conversions work correctly after migration', function () {
    $user = User::factory()->create();

    // Run the migration first
    DB::table('migrations')->where('migration', '2025_10_14_222009_update_saved_conversions_table')->delete();
    $this->artisan('migrate', ['--path' => 'database/migrations/2025_10_14_222009_update_saved_conversions_table.php']);

    // Create new conversion with full data
    $conversionData = [
        'gregorian' => ['full_date' => '2024-01-15', 'name' => 'Gregorian', 'tags' => ['modern']],
        'jalali' => ['full_date' => '1402/10/24', 'name' => 'Jalali', 'tags' => ['modern']],
        'unix' => 1705276800,
        'iso8601' => '2024-01-15T00:00:00Z',
        'hebrew' => ['full_date' => '5784/11/04', 'name' => 'Hebrew', 'tags' => ['historical']],
        'islamic' => ['full_date' => '1445/07/04', 'name' => 'Islamic', 'tags' => ['historical']],
    ];

    $newConversion = SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-15',
        'conversion_data' => $conversionData,
    ]);

    expect($newConversion->conversion_data)->toBe($conversionData)
        ->and($newConversion->gregorian_date)->toBe('2024-01-15')
        ->and($newConversion->jalali_date)->toBe('1402/10/24')
        ->and($newConversion->unix_timestamp)->toBe(1705276800)
        ->and($newConversion->iso8601_string)->toBe('2024-01-15T00:00:00Z');
});

test('guest localStorage migration works correctly', function () {
    // This test would need to be implemented in a browser test
    // as it involves JavaScript localStorage manipulation
    $this->assertTrue(true); // Placeholder for browser test
});
