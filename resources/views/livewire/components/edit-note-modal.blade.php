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
            class="relative inline-block align-bottom bg-white dark:bg-theme-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full"
            @click.stop
        >
            <!-- Modal Header -->
            <div class="bg-theme-100 dark:bg-theme-700 px-6 py-4 border-b border-theme-200 dark:border-theme-600">
                <h3 class="text-lg font-semibold text-theme-900 dark:text-theme-50">
                    {{ $currentNote ? 'Edit Note' : 'Add Note' }}
                </h3>
            </div>

            <!-- Modal Body -->
            <div class="bg-white dark:bg-theme-800 px-6 py-4">
                <div class="mb-4">
                    <label for="edit-note-textarea" class="block text-sm font-medium text-theme-700 dark:text-theme-300 mb-2">
                        Note (optional)
                    </label>
                    <textarea
                        id="edit-note-textarea"
                        wire:model="currentNote"
                        rows="4"
                        maxlength="2048"
                        placeholder="e.g., Birthday, Anniversary, Project Deadline..."
                        class="w-full px-3 py-2 border border-theme-300 dark:border-theme-600 rounded-lg focus:ring-2 focus:ring-theme-500 focus:border-transparent bg-white dark:bg-theme-900 text-theme-900 dark:text-theme-50 placeholder-theme-400 dark:placeholder-theme-500"
                    ></textarea>
                    <p class="mt-1 text-xs text-theme-500 dark:text-theme-400">
                        <span x-text="$wire.currentNote.length"></span>/2048 characters
                    </p>
                    @error('currentNote')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-theme-50 dark:bg-theme-900 px-6 py-4 flex justify-end gap-3">
                <button
                    wire:click="closeModal"
                    type="button"
                    class="px-4 py-2 border border-theme-300 dark:border-theme-600 rounded-lg text-theme-700 dark:text-theme-300 hover:bg-theme-100 dark:hover:bg-theme-800 transition-colors"
                >
                    Cancel
                </button>
                <button
                    wire:click="saveNote"
                    type="button"
                    class="px-4 py-2 bg-theme-500 hover:bg-theme-600 text-white rounded-lg transition-colors"
                >
                    Save
                </button>
            </div>
        </div>
    </div>
</div>
