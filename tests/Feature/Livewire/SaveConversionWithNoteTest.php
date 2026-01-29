<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use App\Services\DateTimeConverterService;
use Livewire\Volt\Volt;
use Mockery;
use Mockery\MockInterface;

beforeEach(function () {
    $this->mock(DateTimeConverterService::class, function (MockInterface $mock) {
        $mock->shouldReceive('convert')
            ->andReturn([
                'gregorian' => [
                    'full_date' => '2024-01-15',
                    'name' => 'Gregorian',
                    'tags' => ['modern', 'solar'],
                ],
                'jalali' => [
                    'full_date' => '1402/10/25',
                    'name' => 'Jalali',
                    'tags' => ['modern', 'solar'],
                ],
                'unix' => 1705276800,
                'iso8601' => '2024-01-15T00:00:00+00:00',
            ]);

        $mock->shouldReceive('getFullConversionResults')
            ->andReturn([
                'gregorian' => [
                    'full_date' => '2024-01-15',
                    'name' => 'Gregorian',
                    'tags' => ['modern', 'solar'],
                ],
                'jalali' => [
                    'full_date' => '1402/10/25',
                    'name' => 'Jalali',
                    'tags' => ['modern', 'solar'],
                ],
                'unix' => 1705276800,
                'iso8601' => '2024-01-15T00:00:00+00:00',
            ]);
    });
});

afterEach(function () {
    Mockery::close();
});

test('authenticated user can save conversion with note via save-conversion-with-note event', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: 'Birthday reminder')
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'note' => 'Birthday reminder',
    ]);
});

test('authenticated user can save conversion without note using empty string', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: '')
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'note' => '',
    ]);
});

test('note whitespace is trimmed automatically on save', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: '  Birthday  ')
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'note' => 'Birthday',
    ]);
});

test('note with only whitespace is saved as empty string', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: '   ')
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'note' => '',
    ]);
});

test('note longer than 2048 characters is rejected', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $longNote = str_repeat('a', 2049);

    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: $longNote)
        ->assertNotDispatched('conversion-saved');

    $this->assertDatabaseCount('saved_conversions', 0);
});

test('legacy save-conversion event still works without note parameter', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion')
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'note' => '',
    ]);
});

test('duplicate conversion check works regardless of note differences', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: 'First note')
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseCount('saved_conversions', 1);

    $component->dispatch('save-conversion-with-note', note: 'Different note')
        ->assertNotDispatched('conversion-saved');

    $this->assertDatabaseCount('saved_conversions', 1);

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'note' => 'First note',
    ]);
});

test('guest users cannot save notes via Livewire', function () {
    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: 'Guest note')
        ->assertNotDispatched('conversion-saved');

    $this->assertDatabaseCount('saved_conversions', 0);
});

test('note is saved with conversion data structure', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: 'Project deadline');

    $conversion = $user->savedConversions()->first();

    expect($conversion->note)->toBe('Project deadline')
        ->and($conversion->conversion_data)->toBeArray()
        ->and($conversion->conversion_data)->toHaveKey('gregorian')
        ->and($conversion->conversion_data)->toHaveKey('jalali');
});

test('multiline notes are preserved', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $multilineNote = "Important meeting\nWith client\nAt 2 PM";

    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: $multilineNote)
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'note' => $multilineNote,
    ]);
});

test('special characters in notes are preserved', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $specialNote = 'Meeting @ 2:00 PM - Discuss Q1 results & plan Q2 strategy!';

    Volt::test('landing')
        ->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate')
        ->dispatch('save-conversion-with-note', note: $specialNote)
        ->assertDispatched('conversion-saved');

    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'note' => $specialNote,
    ]);
});
