<?php

use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\state;

state(['showModal' => false, 'note' => '']);

$openModal = function () {
    $this->showModal = true;
    $this->note = '';
};

$closeModal = function () {
    $this->showModal = false;
    $this->note = '';
};

$saveWithNote = function () {
    // Trim and validate note length
    $this->note = trim($this->note);
    
    if (strlen($this->note) > 1000) {
        $this->addError('note', 'Note must not exceed 1000 characters.');
        return;
    }
    
    if (Auth::check()) {
        // Dispatch to parent component for authenticated save
        $this->dispatch('save-conversion-with-note', note: $this->note);
    } else {
        // Dispatch to Alpine for guest save
        $this->js("
            window.dispatchEvent(new CustomEvent('save-guest-conversion-with-note', {
                detail: { note: " . json_encode($this->note) . " }
            }))
        ");
    }
    
    $this->closeModal();
};

?>

<div>
    <!-- Trigger Button (to be placed in results grid) -->
    <button
        wire:click="openModal"
        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition-colors"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
        </svg>
        Save Conversion
    </button>

    <!-- Modal -->
    <div
        x-data="{ show: @entangle('showModal') }"
        x-show="show"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape.window="show = false"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            @click="$wire.closeModal()"
        ></div>

        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white dark:bg-theme-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            >
                <!-- Modal Header -->
                <div class="bg-theme-100 dark:bg-theme-700 px-6 py-4 border-b border-theme-200 dark:border-theme-600">
                    <h3 class="text-lg font-semibold text-theme-900 dark:text-theme-50">
                        Save Conversion
                    </h3>
                </div>

                <!-- Modal Body -->
                <div class="bg-white dark:bg-theme-800 px-6 py-4">
                    <div class="mb-4">
                        <label for="conversion-note" class="block text-sm font-medium text-theme-700 dark:text-theme-300 mb-2">
                            Add a note (optional)
                        </label>
                        <textarea
                            id="conversion-note"
                            wire:model="note"
                            rows="4"
                            maxlength="1000"
                            placeholder="e.g., Birthday, Anniversary, Project Deadline..."
                            class="w-full px-3 py-2 border border-theme-300 dark:border-theme-600 rounded-lg focus:ring-2 focus:ring-theme-500 focus:border-transparent bg-white dark:bg-theme-900 text-theme-900 dark:text-theme-50 placeholder-theme-400 dark:placeholder-theme-500"
                        ></textarea>
                        <p class="mt-1 text-xs text-theme-500 dark:text-theme-400">
                            <span x-text="$wire.note.length"></span>/1000 characters
                        </p>
                        @error('note')
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
                        wire:click="saveWithNote"
                        type="button"
                        class="px-4 py-2 bg-theme-500 hover:bg-theme-600 text-white rounded-lg transition-colors"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>