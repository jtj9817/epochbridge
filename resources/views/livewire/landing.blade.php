<?php

use App\Services\DateTimeConverterService;
use Livewire\Attributes\Validate;
use function Livewire\Volt\{state, mount, layout};

layout('components.layouts.public');

#[Validate('required|date_format:Y-m-d', message: 'Please enter a valid date in YYYY-MM-DD format.')]
state('dateInput', '');

state('conversionResults', []);
state('conversionError', '');

mount(function () {
    // Initialize with today's date in YYYY-MM-DD format
    $this->dateInput = now()->format('Y-m-d');
});

$setToday = function () {
    $this->dateInput = now()->format('Y-m-d');
};

$setYesterday = function () {
    $this->dateInput = now()->subDay()->format('Y-m-d');
};

$setTomorrow = function () {
    $this->dateInput = now()->addDay()->format('Y-m-d');
};

$convertDate = function (DateTimeConverterService $converter) {
    $this->reset('conversionResults', 'conversionError');
    $this->validate();

    try {
        $this->conversionResults = $converter->convert($this->dateInput);
    } catch (\InvalidArgumentException $e) {
        $this->conversionError = 'An unexpected error occurred. Please try again.';
    }
};

?>

<div id="landing-page-container" class="min-h-screen bg-theme-50 dark:bg-theme-900" 
     x-data="{ 
        showResults: @entangle('conversionResults').live,
        inputValue: @entangle('dateInput').live 
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
                    
                    <!-- Main Input Field -->
                    <div class="mb-8">
                        <label for="date-input" class="block text-sm font-medium text-theme-500 dark:text-theme-100 mb-3">
                            Enter your date
                        </label>
                        <div class="relative">
                            <input 
                                type="date" 
                                id="date-input"
                                wire:model.live="dateInput"
                                class="w-full px-6 py-4 text-lg font-medium text-center border-2 border-theme-100 dark:border-theme-500 rounded-lg bg-theme-50 dark:bg-theme-900 text-theme-900 dark:text-theme-50 focus:outline-none focus:ring-2 focus:ring-theme-300 dark:focus:ring-theme-100 shadow-inner transition"
                                placeholder="YYYY-MM-DD"
                            />
                        </div>
                        @error('dateInput')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
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
                
                <h2 id="conversion-results-heading" class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6">
                    Conversion Results
                </h2>
                
                <div id="results-grid" class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @if (!empty($conversionResults))
                        <!-- Gregorian Calendar -->
                        <div id="result-card-gregorian" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                            <h3 class="font-semibold text-theme-500 dark:text-theme-100 mb-2">Gregorian</h3>
                            <p class="text-lg font-mono text-theme-900 dark:text-theme-50">
                                {{ $conversionResults['gregorian'] ?? 'N/A' }}
                            </p>
                        </div>

                        <!-- Jalali Calendar -->
                        <div id="result-card-jalali" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                            <h3 class="font-semibold text-theme-500 dark:text-theme-100 mb-2">Jalali</h3>
                            <p class="text-lg font-mono text-theme-900 dark:text-theme-50">
                                {{ $conversionResults['jalali'] ?? 'N/A' }}
                            </p>
                        </div>

                        <!-- Unix Timestamp -->
                        <div id="result-card-unix" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                            <h3 class="font-semibold text-theme-500 dark:text-theme-100 mb-2">Unix Timestamp</h3>
                            <p class="text-lg font-mono text-theme-900 dark:text-theme-50">
                                {{ $conversionResults['unix'] ?? 'N/A' }}
                            </p>
                        </div>

                        <!-- ISO 8601 -->
                        <div id="result-card-iso8601" class="bg-theme-100 dark:bg-theme-500 rounded-xl p-6 shadow-lg border border-theme-300 dark:border-theme-300">
                            <h3 class="font-semibold text-theme-500 dark:text-theme-100 mb-2">ISO 8601</h3>
                            <p class="text-lg font-mono text-theme-900 dark:text-theme-50 break-all">
                                {{ $conversionResults['iso8601'] ?? 'N/A' }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>

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
