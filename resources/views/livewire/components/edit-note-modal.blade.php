<?php

use function Livewire\Volt\{state, on};

state([
    'showModal' => false,
    'conversionId' => null,
    'currentNote' => '',
    'isGuest' => false,
]);

on([
    'open-note-modal' => function ($conversionId, $currentNote, $isGuest = false) {
        $this->conversionId = $conversionId;
        $this->currentNote = $currentNote ?? '';
        $this->isGuest = $isGuest;
        $this->showModal = true;
    },
]);

$closeModal = function () {
    $this->showModal = false;
    $this->conversionId = null;
    $this->currentNote = '';
    $this->isGuest = false;
};

$saveNote = function () {
    $this->currentNote = trim($this->currentNote);
    
    if (strlen($this->currentNote) > 2048) {
        $this->addError('currentNote', 'Note must not exceed 2048 characters.');
        return;
    }
    
    // Dispatch save event back to parent
    $this->dispatch('save-note-update', [
        'conversionId' => $this->conversionId,
        'note' => $this->currentNote,
        'isGuest' => $this->isGuest,
    ]);
    
    $this->closeModal();
};

?>

<div
    x-data="{ open: @entangle('showModal') }"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    @keydown.escape.window="open = false"
    style="display: none;"
>
    <!-- Backdrop -->
    <div
        class="fixed inset-0 bg-gray-900 bg-opacity-25 transition-opacity"
        @click="$wire.closeModal()"
    ></div>

    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20">
        <div
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 scale-95"
            class="relative inline-block align-bottom bg-white dark:bg-zinc-900 text-left overflow-hidden border-2 border-theme-300 dark:border-zinc-700 sm:my-8 sm:align-middle sm:max-w-lg w-full"
            @click.stop
        >
            <!-- Modal Header -->
            <div class="bg-theme-100 dark:bg-zinc-800 px-4 py-3 border-b border-theme-300 dark:border-zinc-700">
                <h3 class="text-sm font-bold text-theme-900 dark:text-theme-50 uppercase tracking-widest">
                    {{ $currentNote ? 'Edit Note' : 'Add Note' }}
                </h3>
            </div>

            <!-- Modal Body -->
            <div class="bg-white dark:bg-zinc-900 px-4 py-4">
                <textarea
                    id="edit-note-textarea"
                    wire:model="currentNote"
                    rows="6"
                    maxlength="2048"
                    placeholder="e.g., Birthday, Anniversary, Project Deadline..."
                    class="block w-full px-3 py-2 border-2 border-theme-300 dark:border-zinc-600 focus:outline-none focus:border-theme-500 dark:focus:border-theme-100 bg-white dark:bg-zinc-800 text-theme-900 dark:text-theme-50 placeholder-theme-400 dark:placeholder-zinc-500 resize-none font-mono text-sm"
                ></textarea>
                <div class="mt-2 flex justify-end">
                    <p class="text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider">
                        <span x-text="$wire.currentNote.length"></span>/2048
                    </p>
                </div>
                @error('currentNote')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400 uppercase tracking-wider">{{ $message }}</p>
                @enderror
            </div>

            <!-- Modal Footer -->
            <div class="bg-theme-50 dark:bg-zinc-800 px-4 py-3 flex justify-end gap-3 border-t border-theme-300 dark:border-zinc-700">
                <button
                    wire:click="closeModal"
                    type="button"
                    class="px-4 py-2 border-2 border-theme-300 dark:border-zinc-600 text-theme-700 dark:text-zinc-300 hover:bg-theme-100 dark:hover:bg-zinc-700 text-xs font-bold uppercase tracking-wider"
                >
                    Cancel
                </button>
                <button
                    wire:click="saveNote"
                    type="button"
                    class="px-4 py-2 bg-theme-500 hover:bg-theme-300 border-2 border-theme-500 hover:border-theme-300 text-white text-xs font-bold uppercase tracking-wider"
                >
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
