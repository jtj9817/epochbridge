<?php

use Illuminate\Support\Js;
use function Livewire\Volt\{state, computed};

state([
    'conversionResults' => [],
    'conversionError' => '',
    'searchTerm' => '',
    'selectedTypes' => [],
])->reactive();

$scalarResultKeys = ['unix', 'iso8601', 'julian-day', 'modified-julian-day'];

$filteredResults = computed(function() use ($scalarResultKeys) {
    $conversionResults = is_array($this->conversionResults) ? $this->conversionResults : [];

    $allResults = $conversionResults;
    $searchTerm = strtolower((string) $this->searchTerm);
    $selectedTypes = is_array($this->selectedTypes) ? $this->selectedTypes : [];
    $results = [];

    foreach ($allResults as $key => $data) {
        if (in_array($key, $scalarResultKeys, true)) {
            continue;
        }

        if (! is_array($data)) {
            continue;
        }

        $name = strtolower((string) ($data['name'] ?? $key));
        $tags = is_array($data['tags'] ?? null) ? $data['tags'] : [];

        $searchMatch = $searchTerm === '' || str_contains($name, $searchTerm);
        $typeMatch = empty($selectedTypes) || ! empty(array_intersect($selectedTypes, $tags));

        if ($searchMatch && $typeMatch) {
            $results[$key] = $data;
        }
    }

    return $results;
});

$scalarResults = computed(function() use ($scalarResultKeys) {
    $conversionResults = is_array($this->conversionResults) ? $this->conversionResults : [];

    $allResults = $conversionResults;
    $searchTerm = strtolower((string) $this->searchTerm);
    $results = [];

    foreach ($allResults as $key => $data) {
        if (! in_array($key, $scalarResultKeys, true) || ! is_scalar($data)) {
            continue;
        }

        if ($searchTerm !== '' && ! str_contains(strtolower((string) $data), $searchTerm)) {
            continue;
        }

        $results[$key] = $data;
    }

    return $results;
});

$displayResults = computed(function() {
    $structured = is_array($this->filteredResults) ? $this->filteredResults : [];
    $scalars = is_array($this->scalarResults) ? $this->scalarResults : [];

    return array_merge([], $structured, $scalars);
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
    @if (!empty($conversionResults))
        <div id="conversion-results-section"
             class="max-w-4xl mx-auto mt-12">

        @php
            $displayResults = is_array($this->displayResults) ? $this->displayResults : [];
            $copyLines = [];

            foreach ($displayResults as $key => $data) {
                if (is_array($data) && ! isset($data['error'])) {
                    $label = $data['name'] ?? ucwords(str_replace('_', ' ', (string) $key));
                    $value = $data['full_date'] ?? json_encode($data);

                    $copyLines[] = "{$label}: {$value}";
                    continue;
                }

                if (is_scalar($data)) {
                    $label = ucwords(str_replace(['_', '-'], ' ', (string) $key));
                    $copyLines[] = "{$label}: {$data}";
                }
            }

            $copyLines = implode("\n", array_filter($copyLines));
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
                        wire:click="$dispatch('save-conversion')"
                        class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline">
                        Save
                    </button>
                @else
                    <button
                        @click="$dispatch('save-guest-conversion')"
                        :disabled="$root.isConversionSaved()"
                        class="text-sm font-medium hover:underline disabled:cursor-not-allowed"
                        :class="$root.isConversionSaved() ? 'text-green-600 dark:text-green-400' : 'text-theme-500 dark:text-theme-100'"
                        x-text="$root.isConversionSaved() ? 'Saved ✓' : 'Save'">
                        Save
                    </button>
                @endauth
            </div>
        </div>

        <div id="results-grid" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @if (!empty($displayResults))
                @foreach($displayResults as $key => $data)
                    @php
                        $shouldRender = is_array($data) && !isset($data['error']);
                        $scalarMap = is_array($this->scalarResults) ? $this->scalarResults : [];
                        $allowScalar = array_key_exists($key, $scalarMap) && is_scalar($data);
                    @endphp
                    @if($shouldRender || $allowScalar)
                        @switch($key)
                            @case('gregorian') <x-calendars.gregorian-card :data="$data" /> @break
                            @case('jalali') <x-calendars.jalali-card :data="$data" /> @break
                            @case('unix') <x-calendars.unix-card :data="$data" /> @break
                            @case('iso8601') <x-calendars.iso8601-card :data="$data" /> @break
                            @case('jd') <x-calendars.julian-day-card :data="$data" /> @break
                            @case('mjd') <x-calendars.modified-julian-day-card :data="$data" /> @break
                            @case('julian') <x-card.julian-card :data="$data" /> @break
                            @default
                                <x-dynamic-component :component="'calendars.' . str_replace('_', '-', $key) . '-card'" :data="$data" />
                        @endswitch
                    @endif
                @endforeach
            @endif

            @if(empty($displayResults))
                <div class="md:col-span-2 lg:col-span-3 text-center py-8 text-theme-500 dark:text-theme-100">
                    No calendars match your search criteria.
                </div>
            @endif
        </div>
        </div>
    @endif
</div>
