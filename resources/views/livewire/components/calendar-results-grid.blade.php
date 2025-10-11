<?php

use Illuminate\Support\Js;
use function Livewire\Volt\{state, computed};

state([
    'conversionResults' => [],
    'additionalConversionResults' => [],
    'additionalCalendarsLoaded' => false,
    'conversionError' => '',
    'searchTerm' => '',
    'selectedTypes' => [],
]);

$filteredResults = computed(function() {
    $allResults = array_merge($this->conversionResults, $this->additionalConversionResults);

    if (empty($this->searchTerm) && empty($this->selectedTypes)) {
        return $allResults;
    }

    return collect($allResults)->filter(function ($data, $key) {
        if (!is_array($data)) return false;

        $name = $data['name'] ?? $key;
        $tags = $data['tags'] ?? [];

        $searchMatch = empty($this->searchTerm) || str_contains(strtolower($name), strtolower($this->searchTerm));

        $typeMatch = empty($this->selectedTypes) || !empty(array_intersect($this->selectedTypes, $tags));

        return $searchMatch && $typeMatch;
    })->all();
});

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

        @php
            $copyLines = collect($this->filteredResults ?? [])
                ->filter(fn ($data) => is_array($data) && !isset($data['error']))
                ->map(function ($data, $key) {
                    $label = $data['name'] ?? ucwords(str_replace('_', ' ', (string) $key));
                    $value = $data['full_date'] ?? (is_scalar($data) ? $data : json_encode($data));

                    return "{$label}: {$value}";
                })
                ->implode("\n");
        @endphp

        <div class="flex items-center justify-between mb-6">
            <h2 id="conversion-results-heading" class="text-2xl font-bold text-theme-900 dark:text-theme-50">
                Conversion Results
            </h2>
            <div class="flex items-center gap-4">
                <button
                    @click="copyToClipboard({{ Js::from($copyLines) }}, 'all')"
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
            @if (!empty($this->filteredResults))
                @foreach($this->filteredResults as $key => $data)
                    @php
                        $shouldRender = is_array($data) && !isset($data['error']);
                        $allowScalar = in_array($key, ['unix', 'iso8601', 'julian-day', 'modified-julian-day'], true) && is_scalar($data);
                    @endphp
                    @if($shouldRender || $allowScalar)
                        @switch($key)
                            @case('gregorian') <x-calendars.gregorian-card :data="$data" /> @break
                            @case('jalali') <x-calendars.jalali-card :data="$data" /> @break
                            @case('unix') <x-calendars.unix-card :data="$data" /> @break
                            @case('iso8601') <x-calendars.iso8601-card :data="$data" /> @break
                            @case('jd') <x-calendars.julian-day-card :data="$data" /> @break
                            @case('mjd') <x-calendars.modified-julian-day-card :data="$data" /> @break
                            @default
                                <x-dynamic-component :component="'calendars.' . str_replace('_', '-', $key) . '-card'" :data="$data" />
                        @endswitch
                    @endif
                @endforeach
            @endif

            @if(empty($this->filteredResults) && (!empty($conversionResults)))
                <div class="md:col-span-2 lg:col-span-3 text-center py-8 text-theme-500 dark:text-theme-100">
                    No calendars match your search criteria.
                </div>
            @endif
            
            @if(!$additionalCalendarsLoaded && !empty($conversionResults))
                <div x-intersect.once="$wire.dispatch('load-additional-calendars')" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:col-span-2 lg:col-span-3">
                    @for ($i = 0; $i < 6; $i++)
                        <x-calendars.loading-skeleton />
                    @endfor
                </div>
            @endif
        </div>
    </div>
</div>
