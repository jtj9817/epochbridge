<?php

namespace Tests\Feature\Livewire\Components;

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('renders saved conversions for an authenticated user', function () {
    $user = User::factory()->create();
    $conversionData = [
        'gregorian' => [
            'full_date' => '2024-01-01',
            'name' => 'Gregorian',
            'tags' => ['modern', 'solar'],
        ],
        'jalali' => [
            'full_date' => '1402/10/11',
            'name' => 'Jalali',
            'tags' => ['modern', 'solar'],
        ],
        'unix' => 1704067200,
        'iso8601' => '2024-01-01T00:00:00Z',
    ];

    SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-01',
        'conversion_data' => $conversionData,
        'gregorian_date' => '2024-01-01',
        'jalali_date' => '1402/10/11',
        'unix_timestamp' => 1704067200,
        'iso8601_string' => '2024-01-01T00:00:00Z',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->assertSee('2024-01-01')
        ->assertSee('1402/10/11')
        ->assertSee('Gregorian')
        ->assertSee('Jalali');
});

it('renders saved conversions with complete calendar data', function () {
    $user = User::factory()->create();
    $conversionData = [
        'gregorian' => [
            'full_date' => '2024-01-01',
            'name' => 'Gregorian',
            'tags' => ['modern', 'solar'],
        ],
        'jalali' => [
            'full_date' => '1402/10/11',
            'name' => 'Jalali',
            'tags' => ['modern', 'solar'],
        ],
        'unix' => 1704067200,
        'iso8601' => '2024-01-01T00:00:00Z',
        'hebrew' => [
            'full_date' => '5784/10/19',
            'name' => 'Hebrew',
            'tags' => ['historical', 'lunar'],
        ],
        'islamic' => [
            'full_date' => '1445/06/19',
            'name' => 'Islamic',
            'tags' => ['historical', 'lunar'],
        ],
        'chinese' => [
            'full_date' => '2023/11/20',
            'name' => 'Chinese',
            'tags' => ['historical', 'lunisolar'],
        ],
    ];

    SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-01',
        'conversion_data' => $conversionData,
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->assertSee('Gregorian')
        ->assertSee('Jalali')
        ->assertSee('Hebrew')
        ->assertSee('Islamic')
        ->assertSee('Chinese');
});

it('can delete a conversion for an authenticated user', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->for($user)->create([
        'gregorian_date' => '2024-01-01',
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->call('deleteConversion', $conversion->id);

    $this->assertDatabaseMissing('saved_conversions', ['id' => $conversion->id]);
});

it('refreshes conversions when conversion-saved event is dispatched', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('components.saved-conversions-section');
    expect($component->get('savedConversions'))->toHaveCount(0);

    SavedConversion::factory()->for($user)->create();

    $component->dispatch('conversion-saved')
        ->assertSet('savedConversions', fn ($conversions) => $conversions->count() === 1);
});

it('renders old format conversions with backward compatibility', function () {
    $user = User::factory()->create();

    // Create conversion without conversion_data (old format)
    SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-01',
        'gregorian_date' => '2024-01-01',
        'jalali_date' => '1402/10/11',
        'unix_timestamp' => 1704067200,
        'iso8601_string' => '2024-01-01T00:00:00Z',
    ]);

    $this->actingAs($user);

Volt::test('components.saved-conversions-section')
    ->assertSee('2024-01-01')
    ->assertSee('1402/10/11')
    ->assertSee('Gregorian:')
    ->assertSee('Jalali:')
    ->assertSee('Unix:')
    ->assertSee('ISO 8601:')
    ->assertSee('2024-01-01T00:00:00Z')
    ->assertDontSee('N/A');
});

it('displays multiple saved conversions in correct order', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $conversions = SavedConversion::factory()->for($user)->count(3)->create([
        'conversion_data' => [
            'gregorian' => ['full_date' => '2024-01-01', 'name' => 'Gregorian', 'tags' => ['modern']],
            'jalali' => ['full_date' => '1402/10/11', 'name' => 'Jalali', 'tags' => ['modern']],
            'unix' => 1704067200,
            'iso8601' => '2024-01-01T00:00:00Z',
        ],
    ]);

    $component = Volt::test('components.saved-conversions-section');
    expect($component->get('savedConversions'))->toHaveCount(3);
});

it('shows no saved conversions message when empty', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->assertDontSee('Your Saved Conversions');
});

it('handles conversion with errors gracefully', function () {
    $user = User::factory()->create();
    $conversionData = [
        'gregorian' => [
            'full_date' => '2024-01-01',
            'name' => 'Gregorian',
            'tags' => ['modern', 'solar'],
        ],
        'jalali' => [
            'full_date' => '1402/10/11',
            'name' => 'Jalali',
            'tags' => ['modern', 'solar'],
        ],
        'mayan' => [
            'error' => 'Mayan calendar conversion failed',
        ],
    ];

    SavedConversion::factory()->for($user)->create([
        'original_input' => '2024-01-01',
        'conversion_data' => $conversionData,
    ]);

    $this->actingAs($user);

    Volt::test('components.saved-conversions-section')
        ->assertSee('Gregorian')
        ->assertSee('Jalali')
        ->assertDontSee('Mayan'); // Should not display calendar with errors
});
