<?php

use App\Services\Wikimedia\OnThisDayService;
use Carbon\CarbonImmutable;
use function Livewire\Volt\{state, mount, computed};

// All state - standalone flag determines which values are used
state([
    // Mode flag
    'standalone' => false,

    // Internal state for standalone mode
    'internalShowModal' => false,
    'internalDate' => '',
    'internalDateLabel' => '',
    'internalDayInfo' => [],
    'internalLoading' => false,
    'internalError' => '',
]);

state([
    // Props from parent (conversion mode)
    'onThisDayDayInfo' => [],
    'onThisDayLoading' => false,
    'onThisDayError' => '',
    'showOnThisDayModal' => false,
    'onThisDayDateLabel' => '',
])->reactive();

mount(function () {
    if ($this->standalone) {
        $this->internalDate = now()->format('Y-m-d');
        $this->internalDateLabel = now()->format('F j');
    }
});

$openStandaloneModal = function () {
    $this->internalShowModal = true;
    $this->internalDate = now()->format('Y-m-d');
    $this->internalDateLabel = now()->format('F j');
    $this->loadStandaloneData();
};

$closeModal = function () {
    if ($this->standalone) {
        $this->internalShowModal = false;
        $this->internalLoading = false;
    } else {
        $this->dispatch('close-on-this-day');
    }
};

$changeDate = function (string $date) {
    $parsed = CarbonImmutable::createFromFormat('Y-m-d', $date);
    if (!$parsed) {
        return;
    }

    $this->internalDate = $date;
    $this->internalDateLabel = $parsed->format('F j');
    $this->loadStandaloneData();
};

$loadStandaloneData = function (?OnThisDayService $service = null) {
    $service = $service ?? app(OnThisDayService::class);
    $this->internalLoading = true;
    $this->internalError = '';
    $this->internalDayInfo = [];

    try {
        $result = $service->getForDate($this->internalDate);
        if (!empty($result['error'])) {
            $this->internalError = $result['error'];
        } else {
            $this->internalDayInfo = $result['data'] ?? [];
        }
    } catch (\Throwable $e) {
        $this->internalError = 'Unable to load On This Day content right now.';
        \Log::error('On This Day standalone load failed', [
            'date' => $this->internalDate,
            'error' => $e->getMessage(),
        ]);
    }

    $this->internalLoading = false;
};

?>

@php
    // Use internal state for standalone mode, reactive props for conversion mode
    $isStandalone = $this->standalone;
    $dayInfo = $isStandalone
        ? (is_array($this->internalDayInfo) ? $this->internalDayInfo : [])
        : (is_array($this->onThisDayDayInfo) ? $this->onThisDayDayInfo : []);
    $isLoading = $isStandalone ? $this->internalLoading : $this->onThisDayLoading;
    $errorMsg = $isStandalone ? $this->internalError : $this->onThisDayError;
    $dateLabel = $isStandalone ? $this->internalDateLabel : $this->onThisDayDateLabel;
    $showModal = $isStandalone ? $this->internalShowModal : $this->showOnThisDayModal;

    // Section configuration with semantic colors and icons
    $sections = [
        'selected' => [
            'label' => 'Selected',
            'items' => $dayInfo['selected'] ?? [],
            'borderLight' => 'border-l-amber-500',
            'borderDark' => 'dark:border-l-amber-400',
            'bgLight' => 'bg-amber-50',
            'bgDark' => 'dark:bg-amber-900/20',
            'icon' => '<svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>',
        ],
        'events' => [
            'label' => 'Events',
            'items' => $dayInfo['events'] ?? [],
            'borderLight' => 'border-l-blue-500',
            'borderDark' => 'dark:border-l-blue-400',
            'bgLight' => 'bg-blue-50',
            'bgDark' => 'dark:bg-blue-900/20',
            'icon' => '<svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>',
        ],
        'births' => [
            'label' => 'Births',
            'items' => $dayInfo['births'] ?? [],
            'borderLight' => 'border-l-emerald-500',
            'borderDark' => 'dark:border-l-emerald-400',
            'bgLight' => 'bg-emerald-50',
            'bgDark' => 'dark:bg-emerald-900/20',
            'icon' => '<svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>',
        ],
        'deaths' => [
            'label' => 'Deaths',
            'items' => $dayInfo['deaths'] ?? [],
            'borderLight' => 'border-l-zinc-400',
            'borderDark' => 'dark:border-l-zinc-500',
            'bgLight' => 'bg-zinc-100',
            'bgDark' => 'dark:bg-zinc-800/50',
            'icon' => '<svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>',
        ],
        'holidays' => [
            'label' => 'Holidays',
            'items' => $dayInfo['holidays'] ?? [],
            'borderLight' => 'border-l-teal-500',
            'borderDark' => 'dark:border-l-teal-400',
            'bgLight' => 'bg-teal-50',
            'bgDark' => 'dark:bg-teal-900/20',
            'icon' => '<svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor"><path d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zm7-10a1 1 0 01.707.293l.707.707.707-.707A1 1 0 0115.828 3l-.707.707.707.707a1 1 0 01-1.414 1.414L13.707 5.12l-.707.708a1 1 0 01-1.414-1.414l.707-.707-.707-.707A1 1 0 0112 2z"/></svg>',
        ],
    ];
@endphp

@php
    $showModalProp = $isStandalone ? 'internalShowModal' : 'showOnThisDayModal';
@endphp

{{-- Single root wrapper --}}
<div>
    {{-- Trigger Button (standalone mode only) --}}
    @if ($isStandalone)
        <button
            wire:click="openStandaloneModal"
            type="button"
            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium uppercase tracking-wider text-theme-500 dark:text-theme-50 border border-theme-300 dark:border-zinc-600 hover:bg-theme-100 dark:hover:bg-zinc-700 hover:text-theme-900 dark:hover:text-white transition-colors"
            title="On This Day"
        >
            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
            </svg>
            <span class="hidden sm:inline">On This Day</span>
        </button>
    @endif

    {{-- Modal --}}
    <div
        x-data="{ open: false }"
        x-init="
            open = $wire.{{ $showModalProp }};
            $watch(() => $wire.{{ $showModalProp }}, value => { open = value })
        "
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape.window="open = false; $wire.closeModal()"
    >
    {{-- Backdrop --}}
    <div class="fixed inset-0 bg-black/70 z-40" @click="open = false; $wire.closeModal()"></div>

    {{-- Modal Container --}}
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0 relative z-50 pointer-events-none">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative inline-block align-bottom bg-white dark:bg-zinc-900 border-2 border-theme-300 dark:border-zinc-600 text-left overflow-hidden transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full pointer-events-auto"
            @click.stop
        >
            {{-- Header --}}
            <div class="bg-theme-100 dark:bg-zinc-800 px-6 py-4 border-b-2 border-theme-300 dark:border-zinc-600 flex items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 flex-wrap">
                        <h3 class="text-lg font-bold text-theme-900 dark:text-zinc-50 uppercase tracking-wider">
                            On This Day @if($dateLabel && !$isStandalone) — {{ $dateLabel }} @endif
                        </h3>
                        @if ($isStandalone)
                            {{-- Date Picker for standalone mode --}}
                            <div class="flex items-center gap-2">
                                <span class="text-theme-500 dark:text-zinc-400">—</span>
                                <input
                                    type="date"
                                    wire:model.live="internalDate"
                                    wire:change="changeDate($event.target.value)"
                                    class="bg-white dark:bg-zinc-900 border-2 border-theme-300 dark:border-zinc-600 px-3 py-1.5 text-sm font-medium text-theme-900 dark:text-zinc-100 uppercase tracking-wider focus:outline-none focus:border-theme-500 dark:focus:border-zinc-400 transition"
                                />
                                @if ($dateLabel)
                                    <span class="text-sm font-bold text-theme-700 dark:text-zinc-200 uppercase tracking-wider">
                                        {{ $dateLabel }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>
                    <p class="text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-widest mt-1">
                        English Wikipedia Highlights
                    </p>
                </div>
                <button
                    wire:click="closeModal"
                    @click="open = false"
                    type="button"
                    class="flex-shrink-0 p-2 text-theme-500 hover:text-theme-900 dark:text-zinc-400 dark:hover:text-white hover:bg-theme-200/70 dark:hover:bg-zinc-700 transition border border-transparent hover:border-theme-300 dark:hover:border-zinc-600"
                    aria-label="Close"
                >
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>

            {{-- Content --}}
            <div class="bg-zinc-50 dark:bg-zinc-900 px-6 py-6 max-h-[70vh] overflow-y-auto">
                @if ($isLoading)
                    {{-- Loading State --}}
                    <div class="flex items-center gap-3 text-theme-500 dark:text-zinc-400 py-8 justify-center">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm uppercase tracking-wider font-medium">Loading historical highlights...</span>
                    </div>
                @elseif ($errorMsg)
                    {{-- Error State --}}
                    <div class="border-2 border-red-300 dark:border-red-600 bg-red-50 dark:bg-red-900/30 px-4 py-3 text-sm text-red-700 dark:text-red-200">
                        {{ $errorMsg }}
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
                        {{-- Empty State --}}
                        <div class="border-2 border-theme-200 dark:border-zinc-600 bg-theme-50 dark:bg-zinc-800 px-4 py-3 text-sm text-theme-600 dark:text-zinc-300">
                            No historical highlights available for this date yet.
                        </div>
                    @else
                        {{-- Sections --}}
                        <div class="space-y-6">
                            @foreach ($sections as $key => $section)
                                @php
                                    $items = is_array($section['items']) ? $section['items'] : [];
                                    $totalItems = count($items);
                                @endphp
                                @if (!empty($items))
                                    <div x-data="{ expanded: false }">
                                        {{-- Section Header --}}
                                        <div class="flex items-center justify-between mb-3 pb-2 border-b border-theme-200 dark:border-zinc-700">
                                            <div class="flex items-center gap-2">
                                                <span class="text-theme-500 dark:text-zinc-400">
                                                    {!! $section['icon'] !!}
                                                </span>
                                                <h4 class="text-sm font-bold text-theme-800 dark:text-zinc-100 uppercase tracking-widest">
                                                    {{ $section['label'] }}
                                                </h4>
                                                <span class="text-xs text-theme-400 dark:text-zinc-500 uppercase tracking-wider">
                                                    — {{ $totalItems }} {{ $totalItems === 1 ? 'entry' : 'entries' }}
                                                </span>
                                            </div>
                                            @if ($totalItems > 3)
                                                <button
                                                    type="button"
                                                    @click="expanded = !expanded"
                                                    class="flex items-center gap-1.5 px-3 py-1 text-xs uppercase tracking-wider font-medium text-theme-500 dark:text-zinc-400 border border-theme-300 dark:border-zinc-600 hover:bg-theme-100 dark:hover:bg-zinc-800 hover:text-theme-700 dark:hover:text-zinc-200 transition"
                                                >
                                                    <svg
                                                        class="w-3 h-3 transition-transform"
                                                        :class="expanded ? 'rotate-180' : ''"
                                                        viewBox="0 0 20 20"
                                                        fill="currentColor"
                                                    >
                                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <span x-text="expanded ? 'Show less' : 'Show {{ $totalItems - 3 }} more'"></span>
                                                </button>
                                            @endif
                                        </div>

                                        {{-- Entry Cards --}}
                                        <div class="space-y-2">
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
                                                    x-transition:enter="ease-out duration-200"
                                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                                    x-transition:enter-end="opacity-100 translate-y-0"
                                                    class="flex bg-white dark:bg-zinc-800 border border-theme-200 dark:border-zinc-700 border-l-4 {{ $section['borderLight'] }} {{ $section['borderDark'] }} overflow-hidden"
                                                >
                                                    {{-- Year Column --}}
                                                    @if ($year)
                                                        <div class="flex-shrink-0 w-20 {{ $section['bgLight'] }} {{ $section['bgDark'] }} border-r border-theme-200 dark:border-zinc-700 flex items-center justify-center px-3 py-3">
                                                            <span class="text-sm font-bold text-zinc-700 dark:text-zinc-200 tabular-nums">
                                                                {{ $year }}
                                                            </span>
                                                        </div>
                                                    @endif

                                                    {{-- Content --}}
                                                    <div class="flex-1 px-4 py-3 flex items-start justify-between gap-4">
                                                        <p class="text-sm text-zinc-700 dark:text-zinc-200 leading-relaxed">
                                                            {{ $text }}
                                                        </p>
                                                        @if ($url)
                                                            <a
                                                                href="{{ $url }}"
                                                                target="_blank"
                                                                rel="noopener"
                                                                class="flex-shrink-0 flex items-center gap-1 px-2 py-1 text-xs uppercase tracking-wider font-medium text-theme-500 dark:text-zinc-400 border border-theme-300 dark:border-zinc-600 hover:bg-theme-100 dark:hover:bg-zinc-700 hover:text-theme-700 dark:hover:text-zinc-200 transition"
                                                            >
                                                                Read
                                                                <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor">
                                                                    <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z"/>
                                                                    <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z"/>
                                                                </svg>
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

            {{-- Footer --}}
            <div class="bg-theme-100 dark:bg-zinc-800 px-6 py-4 border-t-2 border-theme-300 dark:border-zinc-600 flex justify-end">
                <button
                    wire:click="closeModal"
                    @click="open = false"
                    type="button"
                    class="px-5 py-2 border-2 border-theme-300 dark:border-zinc-600 text-theme-700 dark:text-zinc-300 hover:bg-theme-200 dark:hover:bg-zinc-700 transition-colors text-sm font-medium uppercase tracking-wider"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
    </div>
</div>
