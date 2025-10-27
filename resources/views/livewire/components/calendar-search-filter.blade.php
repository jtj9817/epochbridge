<?php

use function Livewire\Volt\state;
use function Livewire\Volt\updated;

state([
    'searchTerm' => '',
    'selectedTypes' => [],
    'types' => [
        'modern' => 'Modern',
        'historical' => 'Historical',
        'ancient' => 'Ancient',
        'east-asian' => 'East Asian',
        'lunar' => 'Lunar',
        'solar' => 'Solar',
        'lunisolar' => 'Lunisolar',
        'system' => 'System',
    ],
]);

updated([
    'searchTerm' => fn () => $this->dispatch('filters-updated', searchTerm: $this->searchTerm, selectedTypes: $this->selectedTypes),
    'selectedTypes' => fn () => $this->dispatch('filters-updated', searchTerm: $this->searchTerm, selectedTypes: $this->selectedTypes),
]);

?>

<div class="my-8 max-w-4xl mx-auto" x-data="{ showFilters: false }">
    <div class="relative">
        <input
            type="search"
            wire:model.live.debounce.300ms="searchTerm"
            placeholder="Search calendars... (e.g., Jalali, Hebrew, Ancient)"
            class="w-full pl-10 pr-4 py-3 border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
        >
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    <div class="mt-4">
        <button @click="showFilters = !showFilters" class="text-sm text-theme-500 dark:text-theme-100 hover:underline">
            <span x-text="showFilters ? 'Hide Filters' : 'Show Filters'"></span>
        </button>

        <div x-show="showFilters" x-transition class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach($types as $key => $label)
                <label class="flex items-center space-x-2 text-sm text-theme-900 dark:text-theme-50">
                    <input type="checkbox" wire:model.live="selectedTypes" value="{{ $key }}" class="rounded border-theme-300 text-theme-300 focus:ring-theme-300">
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>
