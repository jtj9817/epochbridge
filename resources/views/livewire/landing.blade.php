<?php

use App\Exceptions\CalendarConversionException;
use App\Services\DateTimeConverterService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use function Livewire\Volt\{state, mount, layout};

layout('components.layouts.public');

state('dateInput', '');
state('inputType', 'gregorian');
state('conversionResults', []);
state('conversionError', '');
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
state([
    'additionalConversionResults' => [],
    'additionalCalendarsLoaded' => false,
    'searchTerm' => '',
    'selectedTypes' => [],
]);


mount(function () {
    // Initialize with today's date in YYYY-MM-DD format
    $this->dateInput = now()->format('Y-m-d');
});

#[On('date-selected')]
$setDate = function (string $date) {
    $this->dateInput = $date;
    $this->convertDate();
};

#[On('input-updated')]
$updateInput = function (string $dateInput, string $inputType) {
    $this->dateInput = $dateInput;
    $this->inputType = $inputType;
};

$convertDate = function (DateTimeConverterService $converter) {
    $this->reset('conversionResults', 'conversionError', 'additionalConversionResults', 'additionalCalendarsLoaded');

    try {
        $this->conversionResults = $converter->convert($this->dateInput, $this->inputType);
    } catch (CalendarConversionException | \InvalidArgumentException $e) {
        $this->conversionError = $e->getMessage();
    } catch (\Illuminate\Validation\ValidationException $e) {
        $this->conversionError = $e->validator->errors()->first('dateInput');
    }
};

#[On('load-additional-calendars')]
$loadAdditionalCalendars = function (DateTimeConverterService $converter) {
    if ($this->additionalCalendarsLoaded || empty($this->conversionResults)) {
        return;
    }

    try {
        $this->additionalConversionResults = $converter->convertAdditional($this->dateInput, $this->inputType);
    } catch (\Exception $e) {
        // Silently fail or add a specific error state for additional calendars
    } finally {
        $this->additionalCalendarsLoaded = true;
    }
};

#[On('filters-updated')]
$updateFilters = function(string $searchTerm, array $selectedTypes) {
    $this->searchTerm = $searchTerm;
    $this->selectedTypes = $selectedTypes;
};


#[On('save-conversion')]
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

    $this->dispatch('conversion-saved');
};

?>

<div id="landing-page-container" class="min-h-screen bg-theme-50 dark:bg-theme-900"
     x-data="{
        showResults: @entangle('conversionResults'),
        showSaveLimitNotification: false
     }"
     @save-guest-conversion.window="
        const savedConversions = JSON.parse(localStorage.getItem('savedConversions')) || [];
        if (savedConversions.length >= 3) {
            showSaveLimitNotification = true;
            return;
        }
        const newConversion = JSON.parse(JSON.stringify(showResults));
        newConversion.id = Date.now();
        savedConversions.unshift(newConversion);
        localStorage.setItem('savedConversions', JSON.stringify(savedConversions.slice(0, 3)));
        window.dispatchEvent(new CustomEvent('new-guest-conversion', { detail: { conversion: newConversion }}));
     ">

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

            <div class="max-w-2xl mx-auto">
                <!-- Date Input Section -->
                <livewire:components.calendar-input-section :dateInput="$dateInput" :inputType="$inputType" :inputTypes="$inputTypes" wire:key="input-section" />

                <!-- Quick Action Buttons -->
                <livewire:components.calendar-quick-actions :inputType="$inputType" wire:key="quick-actions" />

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

            <!-- Search and Filter Section -->
            <div x-show="Object.keys(showResults).length > 0" x-transition>
                <livewire:components.calendar-search-filter wire:key="search-filter" />
            </div>

            <!-- Conversion Results Section -->
            <livewire:components.calendar-results-grid
                :conversionResults="$conversionResults"
                :additionalConversionResults="$additionalConversionResults"
                :additionalCalendarsLoaded="$additionalCalendarsLoaded"
                :conversionError="$conversionError"
                :searchTerm="$searchTerm"
                :selectedTypes="$selectedTypes"
                wire:key="results-grid"
            />
            

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

            <!-- Saved Conversions Section -->
            <livewire:components.saved-conversions-section wire:key="saved-conversions" />

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
