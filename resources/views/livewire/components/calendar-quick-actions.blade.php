<?php

use Morilog\Jalali\Jalalian;
use function Livewire\Volt\{state};

state(['inputType']);

$setDate = function (string $dateValue) {
    $this->dispatch('date-selected', date: $dateValue);
};

$setToday = function () {
    $date = match ($this->inputType) {
        'jalali' => Jalalian::fromCarbon(now())->format('Y/m/d'),
        'unix' => now()->startOfDay()->timestamp,
        'iso8601' => now()->startOfDay()->toIso8601String(),
        'jd' => gregoriantojd(now()->month, now()->day, now()->year),
        'mjd' => gregoriantojd(now()->month, now()->day, now()->year) - 2400000.5,
        'hebrew', 'islamic', 'french_republican' => now()->format('Y/m/d'), // Will be converted by service
        default => now()->format('Y-m-d'),
    };
    $this->setDate((string) $date);
};

$setYesterday = function () {
    $date = now()->subDay();
    $dateValue = match ($this->inputType) {
        'jalali' => Jalalian::fromCarbon($date)->format('Y/m/d'),
        'unix' => $date->startOfDay()->timestamp,
        'iso8601' => $date->startOfDay()->toIso8601String(),
        'jd' => gregoriantojd($date->month, $date->day, $date->year),
        'mjd' => gregoriantojd($date->month, $date->day, $date->year) - 2400000.5,
        'chinese' => $date->format('Y/m/d'), // Will be converted by service
        'japanese' => 'Reiwa' . (now()->year - 2018) . '/' . now()->format('m/d'), // Assuming Reiwa started 2019-05-01
        default => $date->format('Y-m-d'),
    };
    $this->setDate((string) $dateValue);
};

$setTomorrow = function () {
    $date = now()->addDay();
    $dateValue = match ($this->inputType) {
        'jalali' => Jalalian::fromCarbon($date)->format('Y/m/d'),
        'unix' => $date->startOfDay()->timestamp,
        'iso8601' => $date->startOfDay()->toIso8601String(),
        'jd' => gregoriantojd($date->month, $date->day, $date->year),
        'mjd' => gregoriantojd($date->month, $date->day, $date->year) - 2400000.5,
        default => $date->format('Y-m-d'),
    };
    $this->setDate((string) $dateValue);
};

?>

<div id="quick-action-buttons" class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
    <button
        id="btn-today"
        wire:click="setToday"
        class="quick-action-btn px-8 py-3 font-semibold rounded-lg border border-theme-300 text-theme-300 hover:bg-theme-300 hover:text-theme-50 dark:border-theme-100 dark:text-theme-100 dark:hover:bg-theme-100 dark:hover:text-theme-900">
        TODAY
    </button>
    <button
        id="btn-yesterday"
        wire:click="setYesterday"
        class="quick-action-btn px-8 py-3 font-semibold rounded-lg border border-theme-300 text-theme-300 hover:bg-theme-300 hover:text-theme-50 dark:border-theme-100 dark:text-theme-100 dark:hover:bg-theme-100 dark:hover:text-theme-900">
        YESTERDAY
    </button>
    <button
        id="btn-tomorrow"
        wire:click="setTomorrow"
        class="quick-action-btn px-8 py-3 font-semibold rounded-lg border border-theme-300 text-theme-300 hover:bg-theme-300 hover:text-theme-50 dark:border-theme-100 dark:text-theme-100 dark:hover:bg-theme-100 dark:hover:text-theme-900">
        TOMORROW
    </button>
</div>
