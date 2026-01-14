<?php

use Carbon\CarbonImmutable;

use function Livewire\Volt\mount;
use function Livewire\Volt\state;

state([
    'internalShowModal' => false,
    'internalDate' => '',
    'internalDateLabel' => '',
    'internalLoadToken' => 0,
]);

mount(function () {
    $this->internalDate = now()->format('Y-m-d');
    $this->internalDateLabel = now()->format('F j');
});

$openModal = function () {
    $this->internalShowModal = true;
    $this->internalDate = now()->format('Y-m-d');
    $this->internalDateLabel = now()->format('F j');
    $this->internalLoadToken++;
};

$closeModal = function () {
    $this->internalShowModal = false;
};

$refreshDate = function () {
    $parsed = CarbonImmutable::createFromFormat('Y-m-d', $this->internalDate);

    if (! $parsed) {
        return;
    }

    $this->internalDateLabel = $parsed->format('F j');
    $this->internalLoadToken++;
};

?>

@php
    $dateLabel = $this->internalDateLabel;
@endphp

<div>
    {{-- Trigger Button --}}
    <button
        wire:click="openModal"
        type="button"
        class="inline-flex items-center gap-2 px-4 py-2 text-xs font-medium uppercase tracking-wider text-theme-500 dark:text-theme-50 border border-theme-300 dark:border-zinc-600 hover:bg-theme-100 dark:hover:bg-zinc-700 hover:text-theme-900 dark:hover:text-white transition-colors"
        title="On This Day"
    >
        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
        </svg>
        <span class="hidden sm:inline">On This Day</span>
    </button>

    {{-- Modal --}}
    <div
        x-data="{ open: @entangle('internalShowModal') }"
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
                                On This Day
                            </h3>
                            <div class="flex items-center gap-2">
                                <span class="text-theme-500 dark:text-zinc-400">—</span>
                                <input
                                    type="date"
                                    wire:model.defer="internalDate"
                                    wire:change="refreshDate"
                                    wire:loading.attr="disabled"
                                    wire:target="refreshDate"
                                    class="bg-white dark:bg-zinc-900 border-2 border-theme-300 dark:border-zinc-600 px-3 py-1.5 text-sm font-medium text-theme-900 dark:text-zinc-100 uppercase tracking-wider focus:outline-none focus:border-theme-500 dark:focus:border-zinc-400 transition disabled:opacity-50 disabled:cursor-wait"
                                />
                                {{-- Inline loading spinner --}}
                                <div wire:loading wire:target="refreshDate" class="flex items-center">
                                    <svg class="animate-spin h-4 w-4 text-theme-500 dark:text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                @if ($dateLabel)
                                    <span wire:loading.remove wire:target="refreshDate" class="text-sm font-bold text-theme-700 dark:text-zinc-200 uppercase tracking-wider">
                                        {{ $dateLabel }}
                                    </span>
                                @endif
                            </div>
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
                    <livewire:components.on-this-day-list
                        :date="$this->internalDate"
                        :active="$this->internalShowModal"
                        :load-token="$this->internalLoadToken"
                        wire:key="'on-this-day-list'"
                    />
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
