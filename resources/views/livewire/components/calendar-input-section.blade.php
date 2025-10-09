<?php

use function Livewire\Volt\{state, rules, updated};

state(['dateInput', 'inputType', 'inputTypes']);

rules(fn () => match ($this->inputType) {
    'gregorian' => ['dateInput' => 'required|date_format:Y-m-d'],
    'jalali' => ['dateInput' => 'required|date_format:Y/m/d'],
    'unix' => ['dateInput' => 'required|integer'],
    'iso8601' => ['dateInput' => 'required|string'],
    'jd', 'mjd' => ['dateInput' => 'required|numeric'],
    'hebrew', 'islamic', 'french_republican', 'chinese', 'babylonian', 'assyrian', 'macedonian', 'coptic', 'egyptian', 'ethiopian', 'mayan' => ['dateInput' => 'required|date_format:Y/m/d'],
    'japanese' => ['dateInput' => 'required|string'], // More complex format: EraYear/MM/DD
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

?>

<div id="date-input-section" class="mb-12">
    <div class="max-w-2xl mx-auto">

        <!-- Input Type Selector -->
        <div class="mb-4">
            <label for="input-type-selector" class="block text-sm font-medium text-theme-500 dark:text-theme-100 mb-2 text-left">
                Input Format
            </label>
            <select
                id="input-type-selector"
                wire:model.live="inputType"
                class="w-full px-4 py-2 text-base border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition">
                @foreach($inputTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <!-- Main Input Field -->
        <div class="mb-8">
            <label for="date-input" class="block text-sm font-medium text-theme-500 dark:text-theme-100 mb-3 text-left">
                Enter your value
            </label>
            <div class="relative">
                <input
                    x-show="$wire.inputType === 'gregorian'"
                    type="date"
                    id="date-input-gregorian"
                    wire:model.live="dateInput"
                    class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                    placeholder="YYYY-MM-DD"
                />
                <input
                    x-show="$wire.inputType === 'jalali'" x-cloak
                    type="text"
                    id="date-input-jalali"
                    wire:model.live="dateInput"
                    class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                    placeholder="YYYY/MM/DD"
                />
                <input
                    x-show="$wire.inputType === 'unix'" x-cloak
                    type="number"
                    id="date-input-unix"
                    wire:model.live="dateInput"
                    class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                    placeholder="e.g., 1672531200"
                />
                <input
                    x-show="$wire.inputType === 'iso8601'" x-cloak
                    type="text"
                    id="date-input-iso8601"
                    wire:model.live="dateInput"
                    class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                    placeholder="e.g., 2023-01-01T00:00:00Z"
                />
                <input
                    x-show="$wire.inputType === 'jd' || $wire.inputType === 'mjd'" x-cloak
                    type="number"
                    step="any"
                    id="date-input-jd"
                    wire:model.live="dateInput"
                    class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                    :placeholder="$wire.inputType === 'jd' ? 'e.g., 2460310.5' : 'e.g., 60309'"
                />
                <input
                    x-show="['hebrew', 'islamic', 'french_republican', 'chinese', 'babylonian', 'assyrian', 'macedonian', 'coptic', 'egyptian', 'ethiopian', 'mayan'].includes($wire.inputType)" x-cloak
                    type="text"
                    id="date-input-calendars"
                    wire:model.live="dateInput"
                    class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                    placeholder="YYYY/MM/DD"
                />
                <input
                    x-show="$wire.inputType === 'japanese'" x-cloak
                    type="text"
                    id="date-input-japanese"
                    wire:model.live="dateInput"
                    class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                    placeholder="Reiwa1/MM/DD or 2023/MM/DD"
                />
            </div>
            @error('dateInput')
                <p class="mt-2 text-sm text-red-600 dark:text-red-400 text-left">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>
