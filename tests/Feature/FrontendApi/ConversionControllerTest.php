<?php

declare(strict_types=1);

use App\Http\Requests\Frontend\ConvertDateRequest;

it('converts a gregorian date successfully', function () {
    $response = $this->postJson(route('frontend.conversions.store'), [
        'date' => '2024-01-15',
        'input_type' => 'gregorian',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'input' => ['date', 'type'],
            'results' => [
                'gregorian' => ['key', 'name', 'full_date'],
                'jalali' => ['key', 'name', 'full_date'],
                'unix',
                'iso8601',
            ],
            'meta' => ['on_this_day_date'],
        ])
        ->assertJsonPath('input.date', '2024-01-15')
        ->assertJsonPath('input.type', 'gregorian')
        ->assertJsonPath('meta.on_this_day_date', '2024-01-15');
});

it('converts a unix timestamp successfully', function () {
    $response = $this->postJson(route('frontend.conversions.store'), [
        'date' => '1705276800',
        'input_type' => 'unix',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('input.type', 'unix')
        ->assertJsonPath('results.gregorian.full_date', '2024-01-15');
});

it('converts a jalali date successfully', function () {
    $response = $this->postJson(route('frontend.conversions.store'), [
        'date' => '1402/10/25',
        'input_type' => 'jalali',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('input.type', 'jalali')
        ->assertJsonPath('results.gregorian.full_date', '2024-01-15');
});

it('normalizes result keys to use underscores', function () {
    $response = $this->postJson(route('frontend.conversions.store'), [
        'date' => '2024-01-15',
        'input_type' => 'gregorian',
    ]);

    $response->assertSuccessful();

    $results = $response->json('results');

    // Keys should use underscores, not hyphens
    expect($results)->toHaveKey('julian_day');
    expect($results)->toHaveKey('modified_julian_day');
    expect($results)->not->toHaveKey('julian-day');
    expect($results)->not->toHaveKey('modified-julian-day');
});

it('returns validation error for missing date', function () {
    $response = $this->postJson(route('frontend.conversions.store'), [
        'input_type' => 'gregorian',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['date']);
});

it('returns validation error for invalid input type', function () {
    $response = $this->postJson(route('frontend.conversions.store'), [
        'date' => '2024-01-15',
        'input_type' => 'invalid_type',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['input_type']);
});

it('returns error for malformed date', function () {
    $response = $this->postJson(route('frontend.conversions.store'), [
        'date' => 'not-a-date',
        'input_type' => 'gregorian',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('error', 'Invalid date format provided for the selected input type.');
});

it('supports all documented input types', function () {
    expect(ConvertDateRequest::INPUT_TYPES)->toContain(
        'gregorian',
        'jalali',
        'unix',
        'iso8601',
        'jd',
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
        'mayan',
    );
});
