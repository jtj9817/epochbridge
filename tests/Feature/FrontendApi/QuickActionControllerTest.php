<?php

declare(strict_types=1);

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
