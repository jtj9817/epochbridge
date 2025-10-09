<?php

use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\{state, on};

state(['savedConversions' => fn () => Auth::check() ? Auth::user()->savedConversions()->get() : collect()]);

$refreshSavedConversions = on('conversion-saved', function () {
    if (Auth::check()) {
        $this->savedConversions = Auth::user()->savedConversions()->get();
    }
});

$deleteConversion = function (int $id) {
    if (Auth::check()) {
        $conversion = Auth::user()->savedConversions()->find($id);
        if ($conversion) {
            $conversion->delete();
            $this->savedConversions = $this->savedConversions->except($id);
        }
    }
};

?>

<div class="mt-16 max-w-4xl mx-auto">
    <!-- Saved Conversions for Guests -->
    @guest
    <div
        id="saved-conversions-guest"
        x-data="{
            savedConversions: [],
            init() {
                this.savedConversions = JSON.parse(localStorage.getItem('savedConversions')) || [];
                window.addEventListener('new-guest-conversion', event => {
                    this.savedConversions.unshift(event.detail.conversion);
                    this.savedConversions = this.savedConversions.slice(0, 3);
                    localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions));
                });
            },
            deleteSaved(id) {
                this.savedConversions = this.savedConversions.filter(item => item.id !== id);
                localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions));
            }
        }"
        x-show="savedConversions.length > 0"
        x-cloak
    >
        <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6 text-center sm:text-left">
            Your Saved Conversions
        </h2>
        <div class="space-y-4">
            <template x-for="item in savedConversions" :key="item.id">
                <x-saved-conversion-item :item="null" is-guest x-bind:item="item" />
            </template>
        </div>
    </div>
    @endguest

    <!-- Saved Conversions for Authenticated Users -->
    @auth
    <div id="saved-conversions-auth">
        @if ($savedConversions->isNotEmpty())
            <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6 text-center sm:text-left">
                Your Saved Conversions
            </h2>
            <div class="space-y-4">
                @foreach($savedConversions as $item)
                    <x-saved-conversion-item :item="$item" wire:key="auth-{{ $item->id }}" />
                @endforeach
            </div>
        @endif
    </div>
    @endauth
</div>
