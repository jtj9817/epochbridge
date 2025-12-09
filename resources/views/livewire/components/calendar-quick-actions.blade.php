<?php

use App\Services\DateTimeConverterService;
use App\Services\HebrewCalendarService;
use App\Services\IslamicCalendarService;
use App\Services\FrenchRepublicanService;
use App\Services\ChineseCalendarService;
use App\Services\JapaneseCalendarService;
use App\Services\CopticCalendarService;
use App\Services\EgyptianCalendarService;
use App\Services\EthiopianCalendarService;
use App\Services\BabylonianCalendarService;
use App\Services\AssyrianCalendarService;
use App\Services\AncientMacedonianService;
use App\Services\JulianCalendarService;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;
use function Livewire\Volt\{state};

state(['inputType']);

$setDate = function (string $dateValue) {
    $this->dispatch('date-selected', date: $dateValue);
};

/**
 * Convert a Carbon date to the appropriate format for the given input type
 */
$formatDateForInputType = function (Carbon $date, string $inputType): string {
    return match ($inputType) {
        'gregorian' => $date->format('Y-m-d'),
        'jalali' => Jalalian::fromCarbon($date)->format('Y/m/d'),
        'unix' => (string) $date->startOfDay()->timestamp,
        'iso8601' => $date->startOfDay()->toIso8601String(),
        'jd' => (string) gregoriantojd($date->month, $date->day, $date->year),
        'mjd' => (string) (gregoriantojd($date->month, $date->day, $date->year) - 2400000.5),
        'hebrew' => app(HebrewCalendarService::class)->fromGregorian($date)['full_date'],
        'islamic' => app(IslamicCalendarService::class)->fromGregorian($date)['full_date'],
        'french_republican' => app(FrenchRepublicanService::class)->fromGregorian($date)['full_date'],
        'chinese' => app(ChineseCalendarService::class)->fromGregorian($date)['full_date'],
        'japanese' => app(JapaneseCalendarService::class)->fromGregorian($date)['full_date'],
        'coptic' => app(CopticCalendarService::class)->fromGregorian($date)['full_date'],
        'egyptian' => app(EgyptianCalendarService::class)->fromGregorian($date)['full_date'],
        'ethiopian' => app(EthiopianCalendarService::class)->fromGregorian($date)['full_date'],
        'babylonian' => app(BabylonianCalendarService::class)->fromGregorian($date)['full_date'],
        'assyrian' => app(AssyrianCalendarService::class)->fromGregorian($date)['full_date'],
        'macedonian' => app(AncientMacedonianService::class)->fromGregorian($date)['full_date'],
        'julian' => app(JulianCalendarService::class)->fromGregorian($date)['full_date'],
        // Mayan uses Gregorian input format (as stated in the input type label)
        'mayan' => $date->format('Y/m/d'),
        default => $date->format('Y-m-d'),
    };
};

$setToday = function () use ($formatDateForInputType) {
    $date = now();
    $dateValue = $formatDateForInputType($date, $this->inputType);
    $this->setDate($dateValue);
};

$setYesterday = function () use ($formatDateForInputType) {
    $date = now()->subDay();
    $dateValue = $formatDateForInputType($date, $this->inputType);
    $this->setDate($dateValue);
};

$setTomorrow = function () use ($formatDateForInputType) {
    $date = now()->addDay();
    $dateValue = $formatDateForInputType($date, $this->inputType);
    $this->setDate($dateValue);
};

?>

<div id="quick-action-buttons" class="flex flex-col sm:flex-row gap-2 justify-center mb-6">
    <button
        id="btn-today"
        wire:click="setToday"
        class="quick-action-btn font-medium text-theme-500 dark:text-theme-100 hover:text-theme-900 dark:hover:text-white">
        TODAY
    </button>
    <button
        id="btn-yesterday"
        wire:click="setYesterday"
        class="quick-action-btn font-medium text-theme-500 dark:text-theme-100 hover:text-theme-900 dark:hover:text-white">
        YESTERDAY
    </button>
    <button
        id="btn-tomorrow"
        wire:click="setTomorrow"
        class="quick-action-btn font-medium text-theme-500 dark:text-theme-100 hover:text-theme-900 dark:hover:text-white">
        TOMORROW
    </button>
</div>
