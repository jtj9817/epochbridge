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
