<?php

use function Livewire\Volt\{state, rules, updated, on};

state(['dateInput', 'inputType', 'inputTypes']);

rules(fn () => match ($this->inputType) {
    'gregorian' => ['dateInput' => 'required|date_format:Y-m-d'],
    'jalali' => ['dateInput' => 'required|date_format:Y/m/d'],
    'unix' => ['dateInput' => 'required|integer'],
    'iso8601' => ['dateInput' => 'required|string'],
    'jd', 'mjd' => ['dateInput' => 'required|numeric'],
    'hebrew', 'islamic', 'french_republican', 'chinese', 'babylonian', 'assyrian', 'macedonian', 'coptic', 'egyptian', 'ethiopian', 'mayan' => ['dateInput' => 'required|date_format:Y/m/d'],
    'japanese' => ['dateInput' => 'required|string'], // EraName Year/MM/DD
    default => ['dateInput' => 'required'],
})->messages([
    'dateInput.required' => 'Please enter a value.',
    'dateInput.date_format' => 'Please enter a valid date. For Gregorian use YYYY-MM-DD. For others, use YYYY/MM/DD.',
    'dateInput.integer' => 'Please enter a valid integer Unix timestamp.',
    'dateInput.numeric' => 'Please enter a valid number.',
]);

updated([
    'dateInput' => fn () => $this->dispatch('input-updated', dateInput: $this->dateInput, inputType: $this->inputType),
    'inputType' => fn () => $this->dispatch('input-updated', dateInput: $this->dateInput, inputType: $this->inputType),
]);

// Listen for date updates from parent (triggered by quick action buttons)
on([
    'update-date-input' => function (string $date) {
        $this->dateInput = $date;
    },
]);

?>

<div id="date-input-section" class="mb-8">
    <div class="max-w-2xl mx-auto">

        <!-- Input Type Selector -->
        <div class="mb-3">
            <label for="input-type-selector" class="block text-xs font-medium text-theme-500 dark:text-theme-100 mb-2 text-left uppercase tracking-wider">
                Input Format
            </label>
            <select
                id="input-type-selector"
                wire:model.live="inputType"
                class="w-full px-3 py-2 text-sm border-2 border-theme-300 dark:border-zinc-600 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:border-theme-500 dark:focus:border-theme-100 transition-colors">
                @foreach($inputTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <!-- Main Input Field -->
        <div class="mb-6">
            <label for="date-input" class="block text-xs font-medium text-theme-500 dark:text-theme-100 mb-2 text-left uppercase tracking-wider">
                Enter Value
            </label>
            <div class="relative datetime-input">
                <input
                    x-show="$wire.inputType === 'gregorian'"
                    type="date"
                    id="date-input-gregorian"
                    wire:model.live="dateInput"
                    class="w-full px-4 py-3 text-base font-medium text-center border-0 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none transition-colors"
                    placeholder="YYYY-MM-DD"
                />
                <input
                    x-show="$wire.inputType === 'jalali'" x-cloak
                    type="text"
                    id="date-input-jalali"
                    wire:model.live.debounce.300ms="dateInput"
                    class="w-full px-4 py-3 text-base font-medium text-center border-0 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none transition-colors"
                    placeholder="YYYY/MM/DD"
                />
                <input
                    x-show="$wire.inputType === 'unix'" x-cloak
                    type="number"
                    id="date-input-unix"
                    wire:model.live.debounce.300ms="dateInput"
                    class="w-full px-4 py-3 text-base font-medium text-center border-0 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none transition-colors"
                    placeholder="e.g., 1672531200"
                />
                <input
                    x-show="$wire.inputType === 'iso8601'" x-cloak
                    type="text"
                    id="date-input-iso8601"
                    wire:model.live.debounce.300ms="dateInput"
                    class="w-full px-4 py-3 text-base font-medium text-center border-0 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none transition-colors"
                    placeholder="e.g., 2023-01-01T00:00:00Z"
                />
                <input
                    x-show="$wire.inputType === 'jd' || $wire.inputType === 'mjd'" x-cloak
                    type="number"
                    step="any"
                    id="date-input-jd"
                    wire:model.live.debounce.300ms="dateInput"
                    class="w-full px-4 py-3 text-base font-medium text-center border-0 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none transition-colors"
                    :placeholder="$wire.inputType === 'jd' ? 'e.g., 2460310.5' : 'e.g., 60309'"
                />
                <input
                    x-show="['hebrew', 'islamic', 'french_republican', 'chinese', 'babylonian', 'assyrian', 'macedonian', 'coptic', 'egyptian', 'ethiopian', 'mayan'].includes($wire.inputType)" x-cloak
                    type="text"
                    id="date-input-calendars"
                    wire:model.live.debounce.300ms="dateInput"
                    class="w-full px-4 py-3 text-base font-medium text-center border-0 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none transition-colors"
                    placeholder="YYYY/MM/DD"
                />
                <input
                    x-show="$wire.inputType === 'japanese'" x-cloak
                    type="text"
                    id="date-input-japanese"
                    wire:model.live.debounce.300ms="dateInput"
                    class="w-full px-4 py-3 text-base font-medium text-center border-0 bg-white dark:bg-zinc-900 text-theme-900 dark:text-theme-50 focus:outline-none transition-colors"
                    placeholder="Reiwa 5/05/01"
                />
            </div>
            @error('dateInput')
                <p class="mt-2 text-xs text-red-600 dark:text-red-400 text-left uppercase tracking-wider">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
