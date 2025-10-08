<?php

use App\Models\SavedConversion;
use App\Services\DateTimeConverterService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;
use function Livewire\Volt\{state, mount, layout, rules};

layout('components.layouts.public');

state('dateInput', '');
state('inputType', 'gregorian');
state('conversionResults', []);
state('conversionError', '');
state(['savedConversions' => fn () => Auth::check() ? Auth::user()->savedConversions : collect()]);
state('inputTypes', [
    'gregorian' => 'Gregorian (YYYY-MM-DD)',
    'jalali' => 'Jalali (YYYY/MM/DD)',
    'unix' => 'Unix Timestamp',
    'iso8601' => 'ISO 8601',
    'jd' => 'Julian Day',
    'mjd' => 'Modified Julian Day',
    'hebrew' => 'Hebrew (YYYY/MM/DD)',
    'islamic' => 'Islamic (YYYY/MM/DD)',
    'french_republican' => 'French Republican (YYYY/MM/DD)',
    'chinese' => 'Chinese (YYYY/MM/DD)',
    'japanese' => 'Japanese (Era Year/MM/DD)',
    'coptic' => 'Coptic (YYYY/MM/DD)',
    'egyptian' => 'Egyptian (YYYY/MM/DD)',
    'ethiopian' => 'Ethiopian (YYYY/MM/DD)',
    'babylonian' => 'Babylonian (YYYY/MM/DD)',
    'assyrian' => 'Assyrian (YYYY/MM/DD)',
    'macedonian' => 'Ancient Macedonian (YYYY/MM/DD)',
    'mayan' => 'Mayan (from Gregorian YYYY/MM/DD)',
]);

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

mount(function () {
    // Initialize with today's date in YYYY-MM-DD format
    $this->dateInput = now()->format('Y-m-d');
});

$setToday = function () {
    $this->dateInput = match ($this->inputType) {
        'jalali' => Jalalian::fromCarbon(now())->format('Y/m/d'),
        'unix' => now()->startOfDay()->timestamp,
        'iso8601' => now()->startOfDay()->toIso8601String(),
        'jd' => GregorianToJD(now()->month, now()->day, now()->year),
        'mjd' => GregorianToJD(now()->month, now()->day, now()->year) - 2400000.5,
        'hebrew', 'islamic', 'french_republican' => now()->format('Y/m/d'), // Will be converted by service
        default => now()->format('Y-m-d'),
    };
};

$setYesterday = function () {
    $date = now()->subDay();
    $this->dateInput = match ($this->inputType) {
        'jalali' => Jalalian::fromCarbon($date)->format('Y/m/d'),
        'unix' => $date->startOfDay()->timestamp,
        'iso8601' => $date->startOfDay()->toIso8601String(),
        'jd' => GregorianToJD($date->month, $date->day, $date->year),
        'mjd' => GregorianToJD($date->month, $date->day, $date->year) - 2400000.5,
        'chinese' => $date->format('Y/m/d'), // Will be converted by service
        'japanese' => 'Reiwa' . (now()->year - 2018) . '/' . now()->format('m/d'), // Assuming Reiwa started 2019-05-01
        default => $date->format('Y-m-d'),
    };
};

$setTomorrow = function () {
    $date = now()->addDay();
    $this->dateInput = match ($this->inputType) {
        'jalali' => Jalalian::fromCarbon($date)->format('Y/m/d'),
        'unix' => $date->startOfDay()->timestamp,
        'iso8601' => $date->startOfDay()->toIso8601String(),
        'jd' => GregorianToJD($date->month, $date->day, $date->year),
        'mjd' => GregorianToJD($date->month, $date->day, $date->year) - 2400000.5,
        default => $date->format('Y-m-d'),
    };
};

$convertDate = function (DateTimeConverterService $converter) {
    $this->reset('conversionResults', 'conversionError');
    $this->validate();

    try {
        $this->conversionResults = $converter->convert($this->dateInput, $this->inputType);
    } catch (\InvalidArgumentException $e) {
        $this->conversionError = $e->getMessage();
    }
};

$saveConversion = function () {
    if (! Auth::check() || empty($this->conversionResults)) {
        return;
    }

    Auth::user()->savedConversions()->create([
        'original_input' => $this->dateInput,
        'gregorian_date' => $this->conversionResults['gregorian']['full_date'],
        'jalali_date' => $this->conversionResults['jalali']['full_date'],
        'unix_timestamp' => $this->conversionResults['unix'],
        'iso8601_string' => $this->conversionResults['iso8601'],
    ]);

    $this->savedConversions = Auth::user()->savedConversions()->get();
};

$deleteConversion = function (int $id) {
    $conversion = Auth::user()->savedConversions()->find($id);
    if ($conversion) {
        $conversion->delete();
        $this->savedConversions = $this->savedConversions->except($id);
    }
};

?>

<div id="landing-page-container" class="min-h-screen bg-theme-50 dark:bg-theme-900" 
     x-data="{ 
        showResults: @entangle('conversionResults').live,
        inputValue: @entangle('dateInput').live,
        inputType: @entangle('inputType').live,
        copiedFeedback: null,
        copyToClipboard(text, type) {
            navigator.clipboard.writeText(text).then(() => {
                this.copiedFeedback = type;
                setTimeout(() => { this.copiedFeedback = null }, 3000);
            });
        },
        savedConversions: JSON.parse(localStorage.getItem('savedConversions')) || [], // Guest saved conversions
        showSaveLimitNotification: false,
        saveConversion() { // Guest save function
            if (this.savedConversions.length >= 3) {
                this.showSaveLimitNotification = true;
                return;
            }
            const newConversion = JSON.parse(JSON.stringify(this.showResults));
            newConversion.id = Date.now();
            this.savedConversions.unshift(newConversion);
            localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions.slice(0, 3)));
        },
        deleteSaved(id) { // Guest delete function
            this.savedConversions = this.savedConversions.filter(item => item.id !== id);
            localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions));
        }
     }">
    
    <!-- Header -->
    <header id="landing-header" class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (Route::has('login'))
            <nav id="auth-navigation" class="flex items-center justify-end gap-4">
                @auth
                    <a id="link-dashboard" href="{{ url('/dashboard') }}" 
                       class="inline-block px-5 py-2 bg-theme-100 dark:bg-theme-500 border border-theme-300 dark:border-theme-300 rounded-lg text-sm font-medium text-theme-500 dark:text-theme-50 transition-colors">
                        Dashboard
                    </a>
                @else
                    <a id="link-login" href="{{ route('login') }}" 
                       class="inline-block px-5 py-2 text-theme-500 dark:text-theme-50 hover:text-theme-900 dark:hover:text-white rounded-lg text-sm font-medium transition-colors">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a id="link-register" href="{{ route('register') }}" 
                           class="inline-block px-5 py-2 bg-theme-300 hover:bg-theme-500 text-white rounded-lg text-sm font-medium transition-colors">
                            Register
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>

    <!-- Main Content -->
    <main id="landing-main-content" class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-4xl mx-auto text-center">
            
            <!-- Hero Section -->
            <div id="hero-section" class="mb-16">
                <h1 id="main-heading" class="text-6xl sm:text-7xl lg:text-8xl font-bold mb-6">
                    <span class="text-theme-900 dark:text-theme-50">Epoch</span><span class="text-theme-300">Bridge</span>
                </h1>
                <p id="subtitle" class="text-xl sm:text-2xl text-theme-500 dark:text-theme-100 mb-8 max-w-3xl mx-auto leading-relaxed">
                    Convert between different date-time formats with ease. 
                    <span class="text-theme-300 dark:text-theme-100 font-semibold">Bridge the gap</span> 
                    between calendars and time systems.
                </p>
                <div class="text-sm text-theme-300 dark:text-theme-300">
                    Supporting Gregorian, Jalali, Unix timestamps, and more
                </div>
            </div>

            <!-- Date Input Section -->
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
                                x-show="inputType === 'gregorian'"
                                type="date" 
                                id="date-input-gregorian"
                                wire:model.live="dateInput"
                                class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                                placeholder="YYYY-MM-DD"
                            />
                            <input 
                                x-show="inputType === 'jalali'" x-cloak
                                type="text" 
                                id="date-input-jalali"
                                wire:model.live="dateInput"
                                class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                                placeholder="YYYY/MM/DD"
                            />
                            <input 
                                x-show="inputType === 'unix'" x-cloak
                                type="number" 
                                id="date-input-unix"
                                wire:model.live="dateInput"
                                class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                                placeholder="e.g., 1672531200"
                            />
                            <input 
                                x-show="inputType === 'iso8601'" x-cloak
                                type="text"
                                id="date-input-iso8601"
                                wire:model.live="dateInput"
                                class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                                placeholder="e.g., 2023-01-01T00:00:00Z"
                            />
                            <input 
                                x-show="inputType === 'jd' || inputType === 'mjd'" x-cloak
                                type="number" 
                                step="any"
                                id="date-input-jd"
                                wire:model.live="dateInput"
                                class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                                :placeholder="inputType === 'jd' ? 'e.g., 2460310.5' : 'e.g., 60309'"
                            />
                            <input 
                                x-show="['hebrew', 'islamic', 'french_republican', 'chinese', 'babylonian', 'assyrian', 'macedonian', 'coptic', 'egyptian', 'ethiopian', 'mayan'].includes(inputType)" x-cloak
                                type="text" 
                                id="date-input-calendars"
                                wire:model.live="dateInput"
                                class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                                placeholder="YYYY/MM/DD"
                            />
                            <input 
                                x-show="inputType === 'japanese'" x-cloak
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

                    <!-- Quick Action Buttons -->
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

                    <!-- Convert Button -->
                    <div class="flex justify-center">
                        <button 
                            id="btn-convert-date"
                            wire:click="convertDate"
                            wire:loading.attr="disabled"
                            class="btn-convert-glow w-full sm:w-auto px-12 py-4 bg-theme-100 text-theme-900 font-bold text-lg rounded-lg shadow-md transition-all duration-300 border-b-4 border-theme-300 hover:bg-theme-300 hover:text-theme-50 active:border-b-0 active:translate-y-1 relative flex items-center justify-center disabled:opacity-75 disabled:cursor-not-allowed">
                            
                            <span wire:loading.remove wire:target="convertDate">
                                Convert Date
                            </span>
                            
                            <span wire:loading wire:target="convertDate" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Converting...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Conversion Error Message -->
            @if ($conversionError)
                <div id="conversion-error" class="mb-8 p-4 max-w-2xl mx-auto bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-red-800 dark:text-red-200 text-sm">
                        {{ $conversionError }}
                    </p>
                </div>
            @endif

            <!-- Conversion Results Section -->
            <div id="conversion-results-section"
                 x-show="Object.keys(showResults).length > 0" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="max-w-4xl mx-auto">
                
                <div class="flex items-center justify-between mb-6">
                    <h2 id="conversion-results-heading" class="text-2xl font-bold text-theme-900 dark:text-theme-50">
                        Conversion Results
                    </h2>
                    <div class="flex items-center gap-4">
                        <button
                            @click="copyToClipboard(
                                `Gregorian: {{ $conversionResults['gregorian']['full_date'] ?? '' }}\n` +
                                `Jalali: {{ $conversionResults['jalali']['full_date'] ?? '' }}\n` +
                                `Unix: {{ $conversionResults['unix'] ?? '' }}\n` +
                                `ISO 8601: {{ $conversionResults['iso8601'] ?? '' }}\n` +
                                `Julian Day: {{ $conversionResults['jd'] ?? '' }}\n` +
                                `Modified Julian Day: {{ $conversionResults['mjd'] ?? '' }}`,
                                'all'
                            )"
                            class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline"
                            :class="{ 'text-green-500': copiedFeedback === 'all' }"
                            x-text="copiedFeedback === 'all' ? 'Copied!' : 'Copy All'">
                            Copy All
                        </button>
                        @auth
                        <button
                            x-show="Object.keys(showResults).length > 0"
                            wire:click="saveConversion"
                            class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline">
                            Save
                        </button>
                        @else
                        <button
                            x-show="Object.keys(showResults).length > 0"
                            @click="saveConversion()"
                            class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline">
                            Save
                        </button>
                        @endauth
                    </div>
                </div>
                
                <div id="results-grid" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @if (!empty($conversionResults))
                        <!-- Gregorian Calendar -->
                        <div id="result-card-gregorian" class="bg-white dark:bg-zinc-800/50 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 flex flex-col">
                            <div class="p-4 border-b border-zinc-200/80 dark:border-zinc-700/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-zinc-700 dark:text-zinc-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    Gregorian
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['gregorian']['full_date'] ?? '' }}', 'gregorian')" title="Copy" class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'gregorian'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'gregorian'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                                    {{ $conversionResults['gregorian']['month_name'] }}
                                </div>
                                <div class="text-6xl font-bold text-blue-600 dark:text-blue-400 my-1">
                                    {{ $conversionResults['gregorian']['day'] }}
                                </div>
                                <div class="text-lg text-zinc-600 dark:text-zinc-300">
                                    {{ $conversionResults['gregorian']['day_of_week'] }}
                                </div>
                                <div class="text-sm font-mono text-zinc-400 dark:text-zinc-500 mt-2">
                                    {{ $conversionResults['gregorian']['year'] }}
                                </div>
                            </div>
                        </div>

                        <!-- Jalali Calendar -->
                        <div id="result-card-jalali" class="bg-teal-50/50 dark:bg-teal-950/30 rounded-xl shadow-lg border border-teal-200 dark:border-teal-800 flex flex-col">
                            <div class="p-4 border-b border-teal-200/80 dark:border-teal-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-teal-800 dark:text-teal-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Jalali
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['jalali']['full_date'] ?? '' }}', 'jalali')" title="Copy" class="text-teal-600 hover:text-teal-900 dark:text-teal-400 dark:hover:text-teal-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'jalali'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'jalali'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-teal-700 dark:text-teal-300 tracking-wider">
                                    {{ $conversionResults['jalali']['month_name'] }}
                                    <span class="block text-xs italic opacity-75 uppercase">{{ $conversionResults['jalali']['month_name_en'] }}</span>
                                </div>
                                <div class="text-6xl font-bold text-teal-600 dark:text-teal-400 my-1">
                                    {{ $conversionResults['jalali']['day'] }}
                                </div>
                                <div class="text-lg text-teal-600 dark:text-teal-300">
                                    {{ $conversionResults['jalali']['day_of_week'] }}
                                    <span class="block text-sm italic opacity-75">{{ $conversionResults['jalali']['day_of_week_en'] }}</span>
                                </div>
                                <div class="text-sm font-mono text-teal-500 dark:text-teal-500 mt-2">
                                    {{ $conversionResults['jalali']['year'] }}
                                </div>
                            </div>
                        </div>

                        <!-- Unix Timestamp -->
                        <div id="result-card-unix" class="bg-zinc-100 dark:bg-zinc-900 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 flex flex-col shadow-green-500/5 dark:shadow-green-400/5">
                            <div class="p-4 border-b border-zinc-200/80 dark:border-zinc-700/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-mono text-sm uppercase tracking-widest text-zinc-700 dark:text-zinc-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Unix
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['unix'] ?? '' }}', 'unix')" title="Copy" class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'unix'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'unix'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <p class="text-3xl font-mono text-green-600 dark:text-green-400 tracking-wider">
                                    {{ $conversionResults['unix'] ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 uppercase tracking-widest">seconds since epoch</p>
                            </div>
                        </div>

                        <!-- ISO 8601 -->
                        <div id="result-card-iso8601" class="bg-zinc-50 dark:bg-black/20 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 flex flex-col">
                            <div class="p-4 border-b border-zinc-200/80 dark:border-zinc-700/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-semibold text-zinc-700 dark:text-zinc-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2h8a2 2 0 002-2v-1a2 2 0 012-2h1.945M7.707 4.293l.707-.707a2 2 0 012.828 0l.707.707M7.707 4.293l.707.707a2 2 0 002.828 0l.707-.707M15 21v-3.5a2.5 2.5 0 00-5 0V21M3 11a9 9 0 1118 0 9 9 0 01-18 0z" />
                                    </svg>
                                    ISO 8601
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['iso8601'] ?? '' }}', 'iso8601')" title="Copy" class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'iso8601'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'iso8601'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <p class="text-xl font-mono text-zinc-800 dark:text-zinc-100 break-all">
                                    {{ $conversionResults['iso8601'] ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 uppercase tracking-widest">UTC format</p>
                            </div>
                        </div>

                        <!-- Julian Day -->
                        <div id="result-card-jd" class="bg-purple-50/50 dark:bg-purple-950/30 rounded-xl shadow-lg border border-purple-200 dark:border-purple-800 flex flex-col">
                            <div class="p-4 border-b border-purple-200/80 dark:border-purple-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-purple-800 dark:text-purple-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Julian Day
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['jd'] ?? '' }}', 'jd')" title="Copy" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'jd'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'jd'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <p class="text-3xl font-mono text-purple-600 dark:text-purple-400 tracking-wider">
                                    {{ $conversionResults['jd'] ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-purple-500 dark:text-purple-500 mt-2 uppercase tracking-widest">Julian Day Number</p>
                            </div>
                        </div>

                        <!-- Modified Julian Day -->
                        <div id="result-card-mjd" class="bg-orange-50/50 dark:bg-orange-950/30 rounded-xl shadow-lg border border-orange-200 dark:border-orange-800 flex flex-col">
                            <div class="p-4 border-b border-orange-200/80 dark:border-orange-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-orange-800 dark:text-orange-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Modified Julian Day
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['mjd'] ?? '' }}', 'mjd')" title="Copy" class="text-orange-600 hover:text-orange-900 dark:text-orange-400 dark:hover:text-orange-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'mjd'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'mjd'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <p class="text-3xl font-mono text-orange-600 dark:text-orange-400 tracking-wider">
                                    {{ $conversionResults['mjd'] ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-orange-500 dark:text-orange-500 mt-2 uppercase tracking-widest">Modified Julian Day</p>
                            </div>
                        </div>

                        <!-- Hebrew Calendar -->
                        @isset($conversionResults['hebrew'])
                        <div id="result-card-hebrew" class="bg-purple-50/50 dark:bg-purple-950/30 rounded-xl shadow-lg border border-purple-200 dark:border-purple-800 flex flex-col">
                            <div class="p-4 border-b border-purple-200/80 dark:border-purple-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-purple-800 dark:text-purple-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    Hebrew
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['hebrew']['full_date'] ?? '' }}', 'hebrew')" title="Copy" class="text-purple-600 hover:text-purple-900 dark:text-purple-400 dark:hover:text-purple-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'hebrew'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'hebrew'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-purple-700 dark:text-purple-300 tracking-wider">
                                    {{ $conversionResults['hebrew']['month_name'] ?? 'N/A' }}
                                </div>
                                <div class="text-6xl font-bold text-purple-600 dark:text-purple-400 my-1">
                                    {{ $conversionResults['hebrew']['day'] ?? 'N/A' }}
                                </div>
                                <div class="text-lg text-purple-600 dark:text-purple-300">
                                    {{ $conversionResults['hebrew']['day_of_week'] ?? 'N/A' }}
                                </div>
                                <div class="text-sm font-mono text-purple-500 dark:text-purple-500 mt-2">
                                    {{ $conversionResults['hebrew']['year'] ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                        @endisset

                        <!-- Islamic Calendar -->
                        @isset($conversionResults['islamic'])
                        <div id="result-card-islamic" class="bg-green-50/50 dark:bg-green-950/30 rounded-xl shadow-lg border border-green-200 dark:border-green-800 flex flex-col">
                            <div class="p-4 border-b border-green-200/80 dark:border-green-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-green-800 dark:text-green-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9" />
                                    </svg>
                                    Islamic
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['islamic']['full_date'] ?? '' }}', 'islamic')" title="Copy" class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'islamic'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'islamic'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-green-700 dark:text-green-300 tracking-wider">
                                    {{ $conversionResults['islamic']['month_name'] ?? 'N/A' }}
                                </div>
                                <div class="text-6xl font-bold text-green-600 dark:text-green-400 my-1">
                                    {{ $conversionResults['islamic']['day'] ?? 'N/A' }}
                                </div>
                                <div class="text-lg text-green-600 dark:text-green-300">
                                    {{ $conversionResults['islamic']['day_of_week'] ?? 'N/A' }}
                                </div>
                                <div class="text-sm font-mono text-green-500 dark:text-green-500 mt-2">
                                    {{ $conversionResults['islamic']['year'] ?? 'N/A' }} AH
                                </div>
                            </div>
                        </div>
                        @endisset

                        <!-- Babylonian Calendar -->
                        @isset($conversionResults['babylonian'])
                        <div id="result-card-babylonian" class="bg-yellow-50/50 dark:bg-yellow-950/30 rounded-xl shadow-lg border border-yellow-200 dark:border-yellow-800 flex flex-col">
                            <div class="p-4 border-b border-yellow-200/80 dark:border-yellow-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-yellow-800 dark:text-yellow-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9V3m-3.5 3.5L12 3m0 0L8.5 6.5M12 21v-3" />
                                    </svg>
                                    Babylonian
                                </h3>
                                @if($conversionResults['babylonian']['disclaimer'] ?? false)
                                    <div x-data="{ show: false }" class="relative">
                                        <button @mouseenter="show = true" @mouseleave="show = false" class="text-yellow-600 dark:text-yellow-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                        <div x-show="show" x-transition class="absolute bottom-full right-0 mb-2 w-64 p-2 text-xs bg-zinc-800 text-white rounded-md z-10">
                                            {{ $conversionResults['babylonian']['disclaimer'] }}
                                        </div>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-yellow-700 dark:text-yellow-300 tracking-wider">
                                    {{ $conversionResults['babylonian']['month_name'] ?? 'N/A' }}
                                </div>
                                <div class="text-6xl font-bold text-yellow-600 dark:text-yellow-400 my-1">
                                    {{ $conversionResults['babylonian']['day'] ?? 'N/A' }}
                                </div>
                                <div class="text-sm font-mono text-yellow-500 dark:text-yellow-500 mt-2">
                                    {{ $conversionResults['babylonian']['year'] ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                        @endisset

                        <!-- Assyrian Calendar -->
                        @isset($conversionResults['assyrian'])
                        <div id="result-card-assyrian" class="bg-gray-50/50 dark:bg-gray-950/30 rounded-xl shadow-lg border border-gray-200 dark:border-gray-800 flex flex-col">
                            <div class="p-4 border-b border-gray-200/80 dark:border-gray-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-gray-800 dark:text-gray-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                    </svg>
                                    Assyrian
                                </h3>
                                @if($conversionResults['assyrian']['disclaimer'] ?? false)
                                <div x-data="{ show: false }" class="relative">
                                    <button @mouseenter="show = true" @mouseleave="show = false" class="text-gray-600 dark:text-gray-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </button>
                                    <div x-show="show" x-transition class="absolute bottom-full right-0 mb-2 w-64 p-2 text-xs bg-zinc-800 text-white rounded-md z-10">
                                        {{ $conversionResults['assyrian']['disclaimer'] }}
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 tracking-wider">
                                    {{ $conversionResults['assyrian']['month_name'] ?? 'N/A' }}
                                </div>
                                <div class="text-6xl font-bold text-gray-600 dark:text-gray-400 my-1">
                                    {{ $conversionResults['assyrian']['day'] ?? 'N/A' }}
                                </div>
                                <div class="text-sm font-mono text-gray-500 dark:text-gray-500 mt-2">
                                    {{ $conversionResults['assyrian']['year'] ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                        @endisset

                        <!-- Ancient Macedonian Calendar -->
                        @isset($conversionResults['macedonian'])
                        <div id="result-card-macedonian" class="bg-indigo-50/50 dark:bg-indigo-950/30 rounded-xl shadow-lg border border-indigo-200 dark:border-indigo-800 flex flex-col">
                            <div class="p-4 border-b border-indigo-200/80 dark:border-indigo-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-indigo-800 dark:text-indigo-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="M12 14l9-5-9-5-9 5 9 5z" /> <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /> <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222 4 2.222V20" />
                                    </svg>
                                    Ancient Macedonian
                                </h3>
                                @if($conversionResults['macedonian']['disclaimer'] ?? false)
                                <div x-data="{ show: false }" class="relative">
                                    <button @mouseenter="show = true" @mouseleave="show = false" class="text-indigo-600 dark:text-indigo-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </button>
                                    <div x-show="show" x-transition class="absolute bottom-full right-0 mb-2 w-64 p-2 text-xs bg-zinc-800 text-white rounded-md z-10">
                                        {{ $conversionResults['macedonian']['disclaimer'] }}
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-indigo-700 dark:text-indigo-300 tracking-wider">
                                    {{ $conversionResults['macedonian']['month_name'] ?? 'N/A' }}
                                </div>
                                <div class="text-6xl font-bold text-indigo-600 dark:text-indigo-400 my-1">
                                    {{ $conversionResults['macedonian']['day'] ?? 'N/A' }}
                                </div>
                                <div class="text-sm font-mono text-indigo-500 dark:text-indigo-500 mt-2">
                                    {{ $conversionResults['macedonian']['year'] ?? 'N/A' }} SE
                                </div>
                            </div>
                        </div>
                        @endisset

                        <!-- Coptic Calendar -->
                        @isset($conversionResults['coptic'])
                        <div id="result-card-coptic" class="bg-red-50/50 dark:bg-red-950/30 rounded-xl shadow-lg border border-red-200 dark:border-red-800 flex flex-col">
                            <div class="p-4 border-b border-red-200/80 dark:border-red-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-red-800 dark:text-red-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Coptic
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['coptic']['full_date'] ?? '' }}', 'coptic')" title="Copy" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'coptic'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'coptic'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-red-700 dark:text-red-300 tracking-wider">
                                    {{ $conversionResults['coptic']['month_name'] ?? 'N/A' }}
                                </div>
                                <div class="text-6xl font-bold text-red-600 dark:text-red-400 my-1">
                                    {{ $conversionResults['coptic']['day'] ?? 'N/A' }}
                                </div>
                                <div class="text-lg text-red-600 dark:text-red-300">
                                    {{ $conversionResults['coptic']['day_of_week'] ?? 'N/A' }}
                                </div>
                                <div class="text-sm font-mono text-red-500 dark:text-red-500 mt-2">
                                    {{ $conversionResults['coptic']['year'] ?? 'N/A' }} AM
                                </div>
                            </div>
                        </div>
                        @endisset

                        <!-- Egyptian Calendar -->
                        @isset($conversionResults['egyptian'])
                        <div id="result-card-egyptian" class="bg-amber-50/50 dark:bg-amber-950/30 rounded-xl shadow-lg border border-amber-200 dark:border-amber-800 flex flex-col">
                            <div class="p-4 border-b border-amber-200/80 dark:border-amber-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-amber-800 dark:text-amber-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Egyptian
                                </h3>
                                @if($conversionResults['egyptian']['disclaimer'] ?? false)
                                <div x-data="{ show: false }" class="relative">
                                    <button @mouseenter="show = true" @mouseleave="show = false" class="text-amber-600 dark:text-amber-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </button>
                                    <div x-show="show" x-transition class="absolute bottom-full right-0 mb-2 w-64 p-2 text-xs bg-zinc-800 text-white rounded-md z-10">
                                        {{ $conversionResults['egyptian']['disclaimer'] }}
                                    </div>
                                </div>
                                @endif
                                <button @click="copyToClipboard('{{ $conversionResults['egyptian']['full_date'] ?? '' }}', 'egyptian')" title="Copy" class="text-amber-600 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'egyptian'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'egyptian'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-amber-700 dark:text-amber-300 tracking-wider">
                                    {{ $conversionResults['egyptian']['month_name'] ?? 'N/A' }}
                                </div>
                                <div class="text-6xl font-bold text-amber-600 dark:text-amber-400 my-1">
                                    {{ $conversionResults['egyptian']['day'] ?? 'N/A' }}
                                </div>
                                <div class="text-sm font-mono text-amber-500 dark:text-amber-500 mt-2">
                                    {{ $conversionResults['egyptian']['year'] ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                        @endisset

                        <!-- Ethiopian Calendar -->
                        @isset($conversionResults['ethiopian'])
                        <div id="result-card-ethiopian" class="bg-emerald-50/50 dark:bg-emerald-950/30 rounded-xl shadow-lg border border-emerald-200 dark:border-emerald-800 flex flex-col">
                            <div class="p-4 border-b border-emerald-200/80 dark:border-emerald-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-emerald-800 dark:text-emerald-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Ethiopian
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['ethiopian']['full_date'] ?? '' }}', 'ethiopian')" title="Copy" class="text-emerald-600 hover:text-emerald-900 dark:text-emerald-400 dark:hover:text-emerald-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'ethiopian'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'ethiopian'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-emerald-700 dark:text-emerald-300 tracking-wider">
                                    {{ $conversionResults['ethiopian']['month_name'] ?? 'N/A' }}
                                </div>
                                <div class="text-6xl font-bold text-emerald-600 dark:text-emerald-400 my-1">
                                    {{ $conversionResults['ethiopian']['day'] ?? 'N/A' }}
                                </div>
                                <div class="text-lg text-emerald-600 dark:text-emerald-300">
                                    {{ $conversionResults['ethiopian']['day_of_week'] ?? 'N/A' }}
                                </div>
                                <div class="text-sm font-mono text-emerald-500 dark:text-emerald-500 mt-2">
                                    {{ $conversionResults['ethiopian']['year'] ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                        @endisset

                        <!-- Mayan Calendar -->
                        @isset($conversionResults['mayan'])
                        <div id="result-card-mayan" class="bg-stone-50/50 dark:bg-stone-950/30 rounded-xl shadow-lg border border-stone-200 dark:border-stone-800 flex flex-col md:col-span-2 lg:col-span-1">
                            <div class="p-4 border-b border-stone-200/80 dark:border-stone-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-stone-800 dark:text-stone-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                    </svg>
                                    Mayan
                                </h3>
                                @if($conversionResults['mayan']['disclaimer'] ?? false)
                                <div x-data="{ show: false }" class="relative">
                                    <button @mouseenter="show = true" @mouseleave="show = false" class="text-stone-600 dark:text-stone-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </button>
                                    <div x-show="show" x-transition class="absolute bottom-full right-0 mb-2 w-64 p-2 text-xs bg-zinc-800 text-white rounded-md z-10">
                                        {{ $conversionResults['mayan']['disclaimer'] }}
                                    </div>
                                </div>
                                @endif
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center space-y-4">
                                <div>
                                    <p class="text-xs text-stone-500 dark:text-stone-400 uppercase tracking-widest">Long Count</p>
                                    <p class="text-2xl font-mono text-stone-700 dark:text-stone-300">{{ $conversionResults['mayan']['long_count'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-stone-500 dark:text-stone-400 uppercase tracking-widest">Tzolk'in</p>
                                    <p class="text-xl font-semibold text-stone-600 dark:text-stone-200">{{ $conversionResults['mayan']['tzolkin'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-stone-500 dark:text-stone-400 uppercase tracking-widest">Haab'</p>
                                    <p class="text-xl font-semibold text-stone-600 dark:text-stone-200">{{ $conversionResults['mayan']['haab'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                        @endisset

                        <!-- French Republican Calendar -->
                        @isset($conversionResults['french_republican'])
                        <div id="result-card-french-republican" class="bg-blue-50/50 dark:bg-blue-950/30 rounded-xl shadow-lg border border-blue-200 dark:border-blue-800 flex flex-col">
                            <div class="p-4 border-b border-blue-200/80 dark:border-blue-800/80 flex items-center justify-between">
                                <h3 class="flex items-center gap-2.5 font-bold text-blue-800 dark:text-blue-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    </svg>
                                    French Republican
                                </h3>
                                <button @click="copyToClipboard('{{ $conversionResults['french_republican']['full_date'] ?? '' }}', 'french_republican')" title="Copy" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-100 transition-colors">
                                    <span x-show="copiedFeedback !== 'french_republican'">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                    <span x-show="copiedFeedback === 'french_republican'" x-cloak class="text-green-500 text-xs">Copied!</span>
                                </button>
                            </div>
                            <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
                                <div class="text-sm font-medium text-blue-700 dark:text-blue-300 tracking-wider">
                                    {{ $conversionResults['french_republican']['month_name'] ?? 'N/A' }}
                                </div>
                                <div class="text-6xl font-bold text-blue-600 dark:text-blue-400 my-1">
                                    {{ $conversionResults['french_republican']['day'] ?? 'N/A' }}
                                </div>
                                <div class="text-lg text-blue-600 dark:text-blue-300">
                                    {{ $conversionResults['french_republican']['day_of_week'] ?? 'N/A' }}
                                </div>
                                <div class="text-sm font-mono text-blue-500 dark:text-blue-500 mt-2">
                                    {{ $conversionResults['french_republican']['year'] ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                        @endisset
                    @endif
                </div>
            </div>


            <!-- Save Limit Notification -->
            <div x-show="showSaveLimitNotification" x-transition x-cloak class="mt-8 max-w-4xl mx-auto p-4 bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 text-yellow-800 dark:text-yellow-200 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 13a1 1 0 110-2 1 1 0 010 2zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-grow">
                        <p class="text-sm">You've reached the guest limit of 3 saved conversions. <a href="{{ route('register') }}" class="font-bold underline hover:text-yellow-700 dark:hover:text-yellow-100">Create an account</a> for unlimited saves.</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <div class="-mx-1.5 -my-1.5">
                            <button @click="showSaveLimitNotification = false" type="button" class="inline-flex bg-yellow-100 dark:bg-transparent rounded-md p-1.5 text-yellow-500 hover:bg-yellow-200 dark:hover:bg-yellow-900/50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 dark:focus:ring-offset-yellow-900/30 focus:ring-yellow-600">
                                <span class="sr-only">Dismiss</span>
                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Saved Conversions for Guests -->
            @guest
            <div id="saved-conversions-guest" class="mt-16 max-w-4xl mx-auto" x-show="savedConversions.length > 0" x-cloak>
                <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6 text-center sm:text-left">
                    Your Saved Conversions
                </h2>
                <div class="space-y-4">
                    <template x-for="item in savedConversions" :key="item.id">
                        <div class="p-4 bg-theme-100 dark:bg-theme-500 rounded-lg shadow-md flex items-center justify-between gap-4">
                            <div class="text-sm text-theme-900 dark:text-theme-50 grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-2 text-center sm:text-left flex-grow">
                                <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Gregorian:</strong> <span x-text="item.gregorian.full_date"></span></div>
                                <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Jalali:</strong> <span x-text="item.jalali.full_date"></span></div>
                                <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Unix:</strong> <span x-text="item.unix"></span></div>
                            </div>
                            <button @click="deleteSaved(item.id)" class="text-red-500 hover:text-red-700 transition-colors flex-shrink-0" title="Delete">
                                <span class="sr-only">Delete</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
            @endguest

            @auth
            <div id="saved-conversions-auth" class="mt-16 max-w-4xl mx-auto">
                @if ($savedConversions->isNotEmpty())
                    <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6 text-center sm:text-left">
                        Your Saved Conversions
                    </h2>
                    <div class="space-y-4">
                        @foreach($savedConversions as $item)
                            <div wire:key="{{ $item->id }}" class="p-4 bg-theme-100 dark:bg-theme-500 rounded-lg shadow-md flex items-center justify-between gap-4">
                                <div class="text-sm text-theme-900 dark:text-theme-50 grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-2 text-center sm:text-left flex-grow">
                                    <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Gregorian:</strong> <span>{{ $item->gregorian_date }}</span></div>
                                    <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Jalali:</strong> <span>{{ $item->jalali_date }}</span></div>
                                    <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Unix:</strong> <span>{{ $item->unix_timestamp }}</span></div>
                                </div>
                                <button wire:click="deleteConversion({{ $item->id }})" wire:confirm="Are you sure you want to delete this saved conversion?" class="text-red-500 hover:text-red-700 transition-colors flex-shrink-0" title="Delete">
                                    <span class="sr-only">Delete</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            @endauth

            <!-- Features Preview -->
            <div id="features-preview-section" class="mt-20 max-w-6xl mx-auto">
                <h2 id="features-preview-heading" class="text-3xl font-bold text-theme-900 dark:text-theme-50 mb-12">
                    Supported Calendar Systems
                </h2>
                
                <div id="features-grid" class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <div id="feature-card-gregorian" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                        <div class="text-theme-300 dark:text-theme-100 text-3xl mb-4">📅</div>
                        <h3 class="text-xl font-semibold text-theme-900 dark:text-theme-50 mb-2">Gregorian Calendar</h3>
                        <p class="text-theme-500 dark:text-theme-100">Standard international calendar system</p>
                    </div>
                    
                    <div id="feature-card-jalali" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                        <div class="text-theme-300 dark:text-theme-100 text-3xl mb-4">🌙</div>
                        <h3 class="text-xl font-semibold text-theme-900 dark:text-theme-50 mb-2">Jalali Calendar</h3>
                        <p class="text-theme-500 dark:text-theme-100">Persian solar calendar system</p>
                    </div>
                    
                    <div id="feature-card-unix" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                        <div class="text-theme-300 dark:text-theme-100 text-3xl mb-4">⏰</div>
                        <h3 class="text-xl font-semibold text-theme-900 dark:text-theme-50 mb-2">Unix Timestamp</h3>
                        <p class="text-theme-500 dark:text-theme-100">Seconds since Unix epoch</p>
                    </div>
                    
                    <div id="feature-card-chinese" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                        <div class="text-theme-300 dark:text-theme-100 text-3xl mb-4">🐉</div>
                        <h3 class="text-xl font-semibold text-theme-900 dark:text-theme-50 mb-2">Chinese Lunisolar Calendar</h3>
                        <p class="text-theme-500 dark:text-theme-100">Traditional Chinese calendar with zodiac and solar terms</p>
                    </div>

                    <div id="feature-card-japanese" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                        <div class="text-theme-300 dark:text-theme-100 text-3xl mb-4">🌸</div>
                        <h3 class="text-xl font-semibold text-theme-900 dark:text-theme-50 mb-2">Japanese Era Calendar</h3>
                        <p class="text-theme-500 dark:text-theme-100">Imperial era-based calendar system (e.g., Reiwa)</p>
                    </div>

                    <div id="feature-card-coptic" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                        <div class="text-theme-300 dark:text-theme-100 text-3xl mb-4">☦️</div>
                        <h3 class="text-xl font-semibold text-theme-900 dark:text-theme-50 mb-2">Coptic Calendar</h3>
                        <p class="text-theme-500 dark:text-theme-100">Alexandrian calendar used by the Coptic Orthodox Church</p>
                    </div>

                    <div id="feature-card-egyptian" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                        <div class="text-theme-300 dark:text-theme-100 text-3xl mb-4">🏺</div>
                        <h3 class="text-xl font-semibold text-theme-900 dark:text-theme-50 mb-2">Ancient Egyptian Calendar</h3>
                        <p class="text-theme-500 dark:text-theme-100">Historical civil calendar with 365 days per year</p>
                    </div>

                    <div id="feature-card-ethiopian" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                        <div class="text-theme-300 dark:text-theme-100 text-3xl mb-4">🇪🇹</div>
                        <h3 class="text-xl font-semibold text-theme-900 dark:text-theme-50 mb-2">Ethiopian Calendar</h3>
                        <p class="text-theme-500 dark:text-theme-100">Principal calendar in Ethiopia with a 13-month structure</p>
                    </div>

                    <div id="feature-card-mayan" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                        <div class="text-theme-300 dark:text-theme-100 text-3xl mb-4">🗿</div>
                        <h3 class="text-xl font-semibold text-theme-900 dark:text-theme-50 mb-2">Mayan Calendar</h3>
                        <p class="text-theme-500 dark:text-theme-100">Complex system with Long Count, Tzolk'in, and Haab' cycles</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer id="landing-footer" class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mt-20">
        <div class="text-center text-theme-500 dark:text-theme-100 text-sm">
            <p>&copy; {{ date('Y') }} EpochBridge. Built with Laravel & the TALL stack.</p>
        </div>
    </footer>
</div>
