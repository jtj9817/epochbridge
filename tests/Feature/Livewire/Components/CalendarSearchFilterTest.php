<?php

namespace Tests\Feature\Livewire\Components;

use Livewire\Volt\Volt;

it('dispatches filters-updated event on search term update', function () {
    Volt::test('components.calendar-search-filter')
        ->set('searchTerm', 'test search')
        ->assertDispatched('filters-updated', searchTerm: 'test search', selectedTypes: []);
});

it('dispatches filters-updated event on selected types update', function () {
    Volt::test('components.calendar-search-filter')
        ->set('selectedTypes', ['modern'])
        ->assertDispatched('filters-updated', searchTerm: '', selectedTypes: ['modern']);
});
