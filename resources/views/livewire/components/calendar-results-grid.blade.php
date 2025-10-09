<?php

use function Livewire\Volt\{state};

state(['conversionResults' => [], 'conversionError' => '']);

?>

<div x-data="{
        copiedFeedback: null,
        copyToClipboard(text, type) {
            navigator.clipboard.writeText(text).then(() => {
                this.copiedFeedback = type;
                setTimeout(() => { this.copiedFeedback = null }, 3000);
            });
        }
     }">
    <!-- Conversion Error Message -->
    @if ($conversionError)
        <div id="conversion-error" class="my-8 p-4 max-w-2xl mx-auto bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-red-800 dark:text-red-200 text-sm">
                {{ $conversionError }}
            </p>
        </div>
    @endif

    <!-- Conversion Results Section -->
    <div id="conversion-results-section"
         x-show="Object.keys(@js($conversionResults)).length > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         class="max-w-4xl mx-auto mt-12">

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
                    x-show="Object.keys(@js($conversionResults)).length > 0"
                    wire:click="$dispatch('save-conversion')"
                    class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline">
                    Save
                </button>
                @else
                <button
                    x-show="Object.keys(@js($conversionResults)).length > 0"
                    @click="$dispatch('save-guest-conversion')"
                    class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline">
                    Save
                </button>
                @endauth
            </div>
        </div>

        <div id="results-grid" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @if (!empty($conversionResults))
                <x-calendars.gregorian-card :data="$conversionResults['gregorian']" />
                <x-calendars.jalali-card :data="$conversionResults['jalali']" />
                <x-calendars.unix-card :data="$conversionResults['unix']" />
                <x-calendars.iso8601-card :data="$conversionResults['iso8601']" />
                <x-calendars.julian-day-card :data="$conversionResults['jd']" />
                <x-calendars.modified-julian-day-card :data="$conversionResults['mjd']" />

                @foreach(['hebrew', 'islamic', 'french_republican', 'chinese', 'japanese', 'coptic', 'egyptian', 'ethiopian', 'babylonian', 'assyrian', 'macedonian', 'mayan'] as $calendar)
                    @isset($conversionResults[$calendar])
                        @if(!isset($conversionResults[$calendar]['error']))
                            <x-dynamic-component :component="'calendars.' . str_replace('_', '-', $calendar) . '-card'" :data="$conversionResults[$calendar]" />
                        @endif
                    @endisset
                @endforeach
            @endif
        </div>
    </div>
</div>
