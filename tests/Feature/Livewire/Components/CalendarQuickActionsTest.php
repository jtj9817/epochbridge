<?php

namespace Tests\Feature\Livewire\Components;

use Livewire\Volt\Volt;
use Morilog\Jalali\Jalalian;

it('renders the calendar quick actions component', function () {
    $this->get('/')->assertSeeLivewire('components.calendar-quick-actions');
});

it('dispatches date-selected event for today with gregorian type', function () {
    $today = now()->format('Y-m-d');
    Volt::test('components.calendar-quick-actions', ['inputType' => 'gregorian'])
        ->call('setToday')
        ->assertDispatched('date-selected', date: $today);
});

it('dispatches date-selected event for today with jalali type', function () {
    $today = Jalalian::fromCarbon(now())->format('Y/m/d');
    Volt::test('components.calendar-quick-actions', ['inputType' => 'jalali'])
        ->call('setToday')
        ->assertDispatched('date-selected', date: $today);
});

it('dispatches date-selected event for yesterday with gregorian type', function () {
    $yesterday = now()->subDay()->format('Y-m-d');
    Volt::test('components.calendar-quick-actions', ['inputType' => 'gregorian'])
        ->call('setYesterday')
        ->assertDispatched('date-selected', date: $yesterday);
});

it('dispatches date-selected event for tomorrow with unix type', function () {
    $tomorrow = now()->addDay()->startOfDay()->timestamp;
    Volt::test('components.calendar-quick-actions', ['inputType' => 'unix'])
        ->call('setTomorrow')
        ->assertDispatched('date-selected', date: (string) $tomorrow);
});

it('dispatches date-selected event for today with modified julian day type', function () {
    $today = now();
    $expectedMjd = (string) (gregoriantojd($today->month, $today->day, $today->year) - 2400000.5);
    Volt::test('components.calendar-quick-actions', ['inputType' => 'mjd'])
        ->call('setToday')
        ->assertDispatched('date-selected', date: $expectedMjd);
});

it('dispatches date-selected event for today with julian day type', function () {
    $today = now();
    $expectedJd = (string) gregoriantojd($today->month, $today->day, $today->year);
    Volt::test('components.calendar-quick-actions', ['inputType' => 'jd'])
        ->call('setToday')
        ->assertDispatched('date-selected', date: $expectedJd);
});

it('dispatches date-selected event for today with hebrew type', function () {
    $today = now();
    $hebrewDate = app(\App\Services\HebrewCalendarService::class)->fromGregorian($today)['full_date'];
    Volt::test('components.calendar-quick-actions', ['inputType' => 'hebrew'])
        ->call('setToday')
        ->assertDispatched('date-selected', date: $hebrewDate);
});

it('dispatches date-selected event for today with islamic type', function () {
    $today = now();
    $islamicDate = app(\App\Services\IslamicCalendarService::class)->fromGregorian($today)['full_date'];
    Volt::test('components.calendar-quick-actions', ['inputType' => 'islamic'])
        ->call('setToday')
        ->assertDispatched('date-selected', date: $islamicDate);
});

it('dispatches date-selected event for today with french republican type', function () {
    $today = now();
    $frenchDate = app(\App\Services\FrenchRepublicanService::class)->fromGregorian($today)['full_date'];
    Volt::test('components.calendar-quick-actions', ['inputType' => 'french_republican'])
        ->call('setToday')
        ->assertDispatched('date-selected', date: $frenchDate);
});

it('dispatches date-selected event for today with japanese type', function () {
    // Create the current era (Reiwa) for testing
    \App\Models\JapaneseEra::firstOrCreate(
        ['name_romanized' => 'Reiwa'],
        [
            'name_japanese' => '令和',
            'name_english' => 'Reiwa',
            'start_date' => '2019-05-01',
            'end_date' => null,
            'historical_context' => 'Current era',
        ]
    );

    $today = now();
    $japaneseDate = app(\App\Services\JapaneseCalendarService::class)->fromGregorian($today)['full_date'];
    Volt::test('components.calendar-quick-actions', ['inputType' => 'japanese'])
        ->call('setToday')
        ->assertDispatched('date-selected', date: $japaneseDate);
});

it('dispatches date-selected event for yesterday with hebrew type', function () {
    $yesterday = now()->subDay();
    $hebrewDate = app(\App\Services\HebrewCalendarService::class)->fromGregorian($yesterday)['full_date'];
    Volt::test('components.calendar-quick-actions', ['inputType' => 'hebrew'])
        ->call('setYesterday')
        ->assertDispatched('date-selected', date: $hebrewDate);
});

it('dispatches date-selected event for tomorrow with islamic type', function () {
    $tomorrow = now()->addDay();
    $islamicDate = app(\App\Services\IslamicCalendarService::class)->fromGregorian($tomorrow)['full_date'];
    Volt::test('components.calendar-quick-actions', ['inputType' => 'islamic'])
        ->call('setTomorrow')
        ->assertDispatched('date-selected', date: $islamicDate);
});
