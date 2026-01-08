<?php

use function Livewire\Volt\state;

state([
    'onThisDayDayInfo' => [],
    'onThisDayLoading' => false,
    'onThisDayError' => '',
    'showOnThisDayModal' => false,
    'onThisDayDateLabel' => '',
])->reactive();

$closeModal = function () {
    $this->dispatch('close-on-this-day');
};

?>

@php
    $dayInfo = is_array($this->onThisDayDayInfo) ? $this->onThisDayDayInfo : [];
    $sections = [
        'selected' => ['label' => 'Selected', 'items' => $dayInfo['selected'] ?? []],
        'events' => ['label' => 'Events', 'items' => $dayInfo['events'] ?? []],
        'births' => ['label' => 'Births', 'items' => $dayInfo['births'] ?? []],
        'deaths' => ['label' => 'Deaths', 'items' => $dayInfo['deaths'] ?? []],
        'holidays' => ['label' => 'Holidays', 'items' => $dayInfo['holidays'] ?? []],
    ];
@endphp

<div
    x-data="{ show: @entangle('showOnThisDayModal') }"
    x-show="show"
    x-cloak
    x-transition.opacity
    class="fixed inset-0 z-50 overflow-y-auto"
    @keydown.escape.window="show = false; $wire.closeModal()"
>
    <div class="fixed inset-0 bg-black/60 z-40" wire:click="closeModal"></div>

    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 relative z-50 pointer-events-none">
        <div
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative inline-block align-bottom bg-white dark:bg-theme-800 rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full pointer-events-auto"
            @click.stop
        >
            <div class="bg-theme-100 dark:bg-theme-700 px-6 py-4 border-b border-theme-200 dark:border-theme-600 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-theme-900 dark:text-theme-50">
                        On This Day @if($onThisDayDateLabel) — {{ $onThisDayDateLabel }} @endif
                    </h3>
                    <p class="text-xs text-theme-500 dark:text-theme-200 uppercase tracking-widest">
                        English Wikipedia Highlights
                    </p>
                </div>
                <button
                    wire:click="closeModal"
                    type="button"
                    class="p-2 rounded-full text-theme-500 hover:text-theme-900 dark:text-theme-200 dark:hover:text-white hover:bg-theme-200/70 dark:hover:bg-theme-600 transition"
                    aria-label="Close"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            <div class="bg-white dark:bg-theme-800 px-6 py-6 space-y-5">
                @if ($onThisDayLoading)
                    <div class="flex items-center gap-3 text-theme-500 dark:text-theme-200">
                        <svg class="animate-spin h-5 w-5 text-theme-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm uppercase tracking-wider">Loading historical highlights...</span>
                    </div>
                @elseif ($onThisDayError)
                    <div class="rounded-xl border border-red-200 dark:border-red-600 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                        {{ $onThisDayError }}
                    </div>
                @else
                    @php
                        $hasContent = false;
                        foreach ($sections as $section) {
                            if (!empty($section['items'])) {
                                $hasContent = true;
                                break;
                            }
                        }
                    @endphp

                    @if (! $hasContent)
                        <div class="rounded-xl border border-theme-200 dark:border-theme-600 bg-theme-50 dark:bg-theme-700/40 px-4 py-3 text-sm text-theme-600 dark:text-theme-200">
                            No historical highlights available for this date yet.
                        </div>
                    @else
                        <div class="grid gap-4">
                            @foreach ($sections as $key => $section)
                                @php
                                    $items = is_array($section['items']) ? $section['items'] : [];
                                @endphp
                                @if (!empty($items))
                                    <div class="rounded-2xl border border-theme-200 dark:border-theme-600 bg-theme-50/80 dark:bg-theme-700/30 p-4" x-data="{ expanded: false }">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-semibold text-theme-800 dark:text-theme-100 uppercase tracking-widest">
                                                {{ $section['label'] }}
                                            </h4>
                                            @if (count($items) > 3)
                                                <button
                                                    type="button"
                                                    @click="expanded = !expanded"
                                                    class="text-xs uppercase tracking-wider text-theme-500 hover:text-theme-900 dark:text-theme-200 dark:hover:text-white"
                                                    x-text="expanded ? 'Show less' : 'Show more'"
                                                ></button>
                                            @endif
                                        </div>

                                        <div class="mt-3 space-y-3">
                                            @foreach ($items as $index => $entry)
                                                @php
                                                    $entry = is_array($entry) ? $entry : [];
                                                    $year = $entry['year'] ?? null;
                                                    $text = $entry['text'] ?? '';
                                                    $page = $entry['pages'][0] ?? null;
                                                    $url = is_array($page) ? ($page['content_urls']['desktop']['page'] ?? null) : null;
                                                @endphp
                                                <div
                                                    x-show="expanded || {{ $index }} < 3"
                                                    x-transition
                                                    class="rounded-xl bg-white dark:bg-theme-800 border border-theme-200 dark:border-theme-600 px-4 py-3"
                                                >
                                                    <div class="flex items-start justify-between gap-4">
                                                        <div>
                                                            @if ($year)
                                                                <p class="text-xs uppercase tracking-widest text-theme-400 dark:text-theme-300">{{ $year }}</p>
                                                            @endif
                                                            <p class="text-sm text-theme-700 dark:text-theme-100 leading-relaxed">
                                                                {{ $text }}
                                                            </p>
                                                        </div>
                                                        @if ($url)
                                                            <a
                                                                href="{{ $url }}"
                                                                target="_blank"
                                                                rel="noopener"
                                                                class="text-xs uppercase tracking-wider text-theme-500 hover:text-theme-900 dark:text-theme-200 dark:hover:text-white shrink-0"
                                                            >
                                                                Read
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            <div class="bg-theme-50 dark:bg-theme-900 px-6 py-4 flex justify-end">
                <button
                    wire:click="closeModal"
                    type="button"
                    class="px-4 py-2 border border-theme-300 dark:border-theme-600 rounded-lg text-theme-700 dark:text-theme-300 hover:bg-theme-100 dark:hover:bg-theme-800 transition-colors"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
