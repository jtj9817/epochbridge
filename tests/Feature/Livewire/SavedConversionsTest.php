<?php

use App\Livewire\Landing;
use App\Models\User;
use App\Services\DateTimeConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('authenticated user can save conversion with complete calendar data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $converter = app(DateTimeConverterService::class);
    $conversionResults = $converter->convert('2024-01-15', 'gregorian');

    Livewire::test(Landing::class)
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->assertSet('conversionResults', function ($results) {
            return isset($results['gregorian']) && isset($results['jalali']);
        })
        ->call('saveConversion', $converter)
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
    ]);

    $savedConversion = $user->savedConversions()->first();
    expect($savedConversion->conversion_data)->toBeArray()
        ->and($savedConversion->conversion_data)->toHaveKey('gregorian')
        ->and($savedConversion->conversion_data)->toHaveKey('jalali')
        ->and($savedConversion->conversion_data)->toHaveKey('unix')
        ->and($savedConversion->conversion_data)->toHaveKey('iso8601');
});

test('authenticated user cannot save conversion without results', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $converter = app(DateTimeConverterService::class);

    Livewire::test(Landing::class)
        ->set('dateInput', '')
        ->call('saveConversion', $converter)
        ->assertNotDispatched('conversion-saved');

    $this->assertDatabaseCount('saved_conversions', 0);
});

test('guest user cannot save conversion through livewire', function () {
    $converter = app(DateTimeConverterService::class);

    Livewire::test(Landing::class)
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->call('saveConversion', $converter)
        ->assertNotDispatched('conversion-saved');

    $this->assertDatabaseCount('saved_conversions', 0);
});

test('saved conversion includes all calendar systems', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $converter = app(DateTimeConverterService::class);
    $fullResults = $converter->getFullConversionResults('2024-01-15', 'gregorian');

    Livewire::test(Landing::class)
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->call('saveConversion', $converter);

    $savedConversion = $user->savedConversions()->first();

    // Check that major calendar systems are included
    expect($savedConversion->conversion_data)->toHaveKeys([
        'gregorian', 'jalali', 'unix', 'iso8601', 'julian-day',
        'hebrew', 'islamic', 'french_republican', 'chinese', 'japanese',
    ]);
});

test('saved conversion maintains backward compatibility fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $converter = app(DateTimeConverterService::class);

    Livewire::test(Landing::class)
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->call('saveConversion', $converter);

    $savedConversion = $user->savedConversions()->first();

    $this->assertDatabaseHas('saved_conversions', [
        'id' => $savedConversion->id,
        'gregorian_date' => '2024-01-15',
        'jalali_date' => $savedConversion->conversion_data['jalali']['full_date'],
        'unix_timestamp' => $savedConversion->conversion_data['unix'],
        'iso8601_string' => $savedConversion->conversion_data['iso8601'],
    ]);
});

test('conversion with different input types saves correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $converter = app(DateTimeConverterService::class);

    // Test with Jalali input
    Livewire::test(Landing::class)
        ->set('dateInput', '1402/10/24')
        ->set('inputType', 'jalali')
        ->call('convertDate')
        ->call('saveConversion', $converter);

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'original_input' => '1402/10/24',
    ]);

    $savedConversion = $user->savedConversions()->first();
    expect($savedConversion->conversion_data)->toBeArray()
        ->and($savedConversion->conversion_data)->toHaveKey('gregorian')
        ->and($savedConversion->conversion_data)->toHaveKey('jalali');
});

test('multiple conversions are saved correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $converter = app(DateTimeConverterService::class);
    $dates = ['2024-01-15', '2024-02-15', '2024-03-15'];

    foreach ($dates as $date) {
        Livewire::test(Landing::class)
            ->set('dateInput', $date)
            ->set('inputType', 'gregorian')
            ->call('convertDate')
            ->call('saveConversion', $converter);
    }

    expect($user->savedConversions()->count())->toBe(3);

    $user->savedConversions()->each(function ($conversion) use ($dates) {
        expect($dates)->toContain($conversion->original_input);
        expect($conversion->conversion_data)->toBeArray();
    });
});

test('conversion error does not save invalid data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $converter = app(DateTimeConverterService::class);

    Livewire::test(Landing::class)
        ->set('dateInput', 'invalid-date')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->assertSet('conversionError', function ($error) {
            return ! empty($error);
        })
        ->call('saveConversion', $converter)
        ->assertNotDispatched('conversion-saved');

    $this->assertDatabaseCount('saved_conversions', 0);
});
