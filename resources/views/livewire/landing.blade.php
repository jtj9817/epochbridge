<?php

use function Livewire\Volt\{state, mount};

state(['dateInput' => '', 'conversionResults' => []]);

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

$convertDate = function () {
    // Placeholder for future API integration
    $this->conversionResults = [
        'gregorian' => $this->dateInput,
        'jalali' => 'Will be converted via API',
        'unix' => strtotime($this->dateInput),
        'iso8601' => date('c', strtotime($this->dateInput)),
    ];
};

?>

<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 dark:from-slate-900 dark:to-slate-800" 
     x-data="{ 
        showResults: @entangle('conversionResults').live,
        inputValue: @entangle('dateInput').live 
     }">
    
    <!-- Header -->
    <header class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @if (Route::has('login'))
            <nav class="flex items-center justify-end gap-4">
                @auth
                    <a href="{{ url('/dashboard') }}" 
                       class="inline-block px-5 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 transition-colors">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" 
                       class="inline-block px-5 py-2 text-slate-700 dark:text-slate-200 hover:text-slate-900 dark:hover:text-white rounded-lg text-sm font-medium transition-colors">
                        Log in
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" 
                           class="inline-block px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                            Register
                        </a>
                    @endif
                @endauth
            </nav>
        @endif
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-4xl mx-auto text-center">
            
            <!-- Hero Section -->
            <div class="mb-16">
                <h1 class="text-6xl sm:text-7xl lg:text-8xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-purple-600 dark:from-blue-400 dark:to-purple-400 mb-6">
                    EpochBridge
                </h1>
                <p class="text-xl sm:text-2xl text-slate-600 dark:text-slate-300 mb-8 max-w-3xl mx-auto leading-relaxed">
                    Convert between different date-time formats with ease. 
                    <span class="text-blue-600 dark:text-blue-400 font-semibold">Bridge the gap</span> 
                    between calendars and time systems.
                </p>
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    Supporting Gregorian, Jalali, Unix timestamps, and more
                </div>
            </div>

            <!-- Date Input Section -->
            <div class="mb-12">
                <div class="max-w-2xl mx-auto">
                    
                    <!-- Main Input Field -->
                    <div class="mb-8">
                        <label for="date-input" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-3">
                            Enter your date
                        </label>
                        <div class="relative">
                            <input 
                                type="date" 
                                id="date-input"
                                wire:model.live="dateInput"
                                class="datetime-input w-full px-6 py-4 text-lg font-medium text-center border-2 border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none"
                                placeholder="YYYY-MM-DD"
                            />
                            
                            <!-- Additional text input for manual entry -->
                            <div class="mt-4">
                                <input 
                                    type="text" 
                                    wire:model.live="dateInput"
                                    class="datetime-input w-full px-6 py-3 text-lg text-center border-2 border-slate-200 dark:border-slate-700 rounded-xl bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none"
                                    placeholder="YYYY-MM-DD or enter any date format"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Quick Action Buttons -->
                    <div class="flex flex-col sm:flex-row gap-4 justify-center mb-8">
                        <button 
                            wire:click="setToday"
                            class="quick-action-btn px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md">
                            TODAY
                        </button>
                        <button 
                            wire:click="setYesterday"
                            class="quick-action-btn px-8 py-3 bg-slate-600 hover:bg-slate-700 text-white font-semibold rounded-lg shadow-md">
                            YESTERDAY
                        </button>
                        <button 
                            wire:click="setTomorrow"
                            class="quick-action-btn px-8 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md">
                            TOMORROW
                        </button>
                    </div>

                    <!-- Convert Button -->
                    <button 
                        wire:click="convertDate"
                        class="glow-border w-full sm:w-auto px-12 py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-bold text-lg rounded-xl shadow-lg transition-all duration-300">
                        Convert Date
                    </button>
                </div>
            </div>

            <!-- Conversion Results Section -->
            <div x-show="Object.keys(showResults).length > 0" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="max-w-4xl mx-auto">
                
                <h2 class="text-2xl font-bold text-slate-800 dark:text-slate-200 mb-6">
                    Conversion Results
                </h2>
                
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    @if (!empty($conversionResults))
                        <!-- Gregorian Calendar -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                            <h3 class="font-semibold text-slate-600 dark:text-slate-400 mb-2">Gregorian</h3>
                            <p class="text-lg font-mono text-slate-900 dark:text-slate-100">
                                {{ $conversionResults['gregorian'] ?? 'N/A' }}
                            </p>
                        </div>

                        <!-- Jalali Calendar -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                            <h3 class="font-semibold text-slate-600 dark:text-slate-400 mb-2">Jalali</h3>
                            <p class="text-lg font-mono text-slate-900 dark:text-slate-100">
                                {{ $conversionResults['jalali'] ?? 'N/A' }}
                            </p>
                        </div>

                        <!-- Unix Timestamp -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                            <h3 class="font-semibold text-slate-600 dark:text-slate-400 mb-2">Unix Timestamp</h3>
                            <p class="text-lg font-mono text-slate-900 dark:text-slate-100">
                                {{ $conversionResults['unix'] ?? 'N/A' }}
                            </p>
                        </div>

                        <!-- ISO 8601 -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                            <h3 class="font-semibold text-slate-600 dark:text-slate-400 mb-2">ISO 8601</h3>
                            <p class="text-lg font-mono text-slate-900 dark:text-slate-100 break-all">
                                {{ $conversionResults['iso8601'] ?? 'N/A' }}
                            </p>
                        </div>
                    @endif
                </div>

                <!-- API Notice -->
                <div class="mt-8 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <p class="text-yellow-800 dark:text-yellow-200 text-sm">
                        <strong>Note:</strong> This is a frontend-only demo. 
                        API integration for comprehensive calendar conversions is coming soon.
                    </p>
                </div>
            </div>

            <!-- Features Preview -->
            <div class="mt-20 max-w-6xl mx-auto">
                <h2 class="text-3xl font-bold text-slate-800 dark:text-slate-200 mb-12">
                    Supported Calendar Systems
                </h2>
                
                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                        <div class="text-blue-600 dark:text-blue-400 text-3xl mb-4">📅</div>
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-slate-200 mb-2">Gregorian Calendar</h3>
                        <p class="text-slate-600 dark:text-slate-400">Standard international calendar system</p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                        <div class="text-green-600 dark:text-green-400 text-3xl mb-4">🌙</div>
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-slate-200 mb-2">Jalali Calendar</h3>
                        <p class="text-slate-600 dark:text-slate-400">Persian solar calendar system</p>
                    </div>
                    
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-lg border border-slate-200 dark:border-slate-700">
                        <div class="text-purple-600 dark:text-purple-400 text-3xl mb-4">⏰</div>
                        <h3 class="text-xl font-semibold text-slate-800 dark:text-slate-200 mb-2">Unix Timestamp</h3>
                        <p class="text-slate-600 dark:text-slate-400">Seconds since Unix epoch</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 mt-20">
        <div class="text-center text-slate-500 dark:text-slate-400 text-sm">
            <p>&copy; {{ date('Y') }} EpochBridge. Built with Laravel & the TALL stack.</p>
        </div>
    </footer>
</div>