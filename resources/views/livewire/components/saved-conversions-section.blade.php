<?php

use App\Models\SavedConversion;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\on;
use function Livewire\Volt\state;

state([
    'savedConversions' => fn () => Auth::check() ? SavedConversion::getPaginatedForUser(Auth::id(), 10) : null,
    'perPage' => 10,
]);

on([
    'conversion-saved' => function () {
        if (Auth::check()) {
            $this->savedConversions = SavedConversion::getPaginatedForUser(Auth::id(), $this->perPage);
        }
    },
    'page-updated' => function ($page) {
        if (Auth::check()) {
            $this->savedConversions = SavedConversion::forUser(Auth::id())
                ->withConversionData()
                ->paginate($this->perPage, ['*'], 'page', $page);
        }
    },
]);

$deleteConversion = function (int $id) {
    if (Auth::check()) {
        $conversion = SavedConversion::where('user_id', Auth::id())->find($id);
        if ($conversion) {
            $conversion->delete();
            // Refresh pagination to maintain correct page counts
            $this->savedConversions = SavedConversion::getPaginatedForUser(Auth::id(), $this->perPage);
        }
    }
};

$updatedPerPage = function ($perPage) {
    $this->perPage = $perPage;
    if (Auth::check()) {
        $this->savedConversions = SavedConversion::getPaginatedForUser(Auth::id(), $this->perPage);
    }
};

$setPage = function ($url) {
    if (Auth::check() && $url) {
        // Extract page number from URL
        $parsedUrl = parse_url($url);
        parse_str($parsedUrl['query'] ?? '', $query);
        $page = $query['page'] ?? 1;

        $this->savedConversions = SavedConversion::forUser(Auth::id())
            ->withConversionData()
            ->paginate($this->perPage, ['*'], 'page', $page);
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
                this.loadSavedConversions();
                window.addEventListener('new-guest-conversion', event => {
                    this.savedConversions.unshift(event.detail.conversion);
                    this.savedConversions = this.savedConversions.slice(0, 3);
                    this.saveToLocalStorage();
                });
            },
            
            loadSavedConversions() {
                try {
                    const data = localStorage.getItem('savedConversions');
                    if (data) {
                        this.savedConversions = JSON.parse(data);
                    }
                } catch (e) {
                    console.warn('Failed to load guest conversions:', e);
                    this.savedConversions = [];
                }
            },
            
            saveToLocalStorage() {
                try {
                    localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions));
                } catch (e) {
                    console.warn('Failed to save guest conversions:', e);
                    // Handle localStorage quota exceeded
                    if (e.name === 'QuotaExceededError') {
                        // Keep only the most recent conversion
                        this.savedConversions = this.savedConversions.slice(0, 1);
                        localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions));
                    }
                }
            },
            
            deleteSaved(id) {
                this.savedConversions = this.savedConversions.filter(item => item.id !== id);
                this.saveToLocalStorage();
            }
        }"
        x-show="savedConversions.length > 0"
        x-cloak
    >
        <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6 text-center sm:text-left">
            Your Saved Conversions
        </h2>
        <div class="space-y-6">
            <template x-for="item in savedConversions" :key="item.id">
                <x-saved-conversion-item :item="null" is-guest x-bind:item="item" />
            </template>
        </div>
    </div>
    @endguest

    <!-- Saved Conversions for Authenticated Users -->
    @auth
    <div id="saved-conversions-auth">
        @if ($savedConversions && $savedConversions->isNotEmpty())
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 text-center sm:text-left">
                    Your Saved Conversions
                </h2>
                
                <!-- Per page selector -->
                <div class="flex items-center gap-2">
                    <label for="per-page" class="text-sm text-theme-600 dark:text-theme-300">Show:</label>
                    <select 
                        id="per-page" 
                        wire:model.live="perPage" 
                        wire:change="updatedPerPage"
                        class="px-3 py-1 text-sm border border-theme-300 dark:border-theme-600 rounded-md bg-theme-50 dark:bg-theme-700 text-theme-900 dark:text-theme-100 focus:ring-2 focus:ring-theme-500 focus:border-transparent"
                    >
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
            
            <div class="space-y-6">
                @foreach($savedConversions as $item)
                    <x-saved-conversion-item :item="$item" wire:key="auth-{{ $item->id }}" />
                @endforeach
            </div>
            
            <!-- Pagination controls -->
            @if ($savedConversions->hasPages())
                <div class="flex items-center justify-between mt-8">
                    <div class="text-sm text-theme-600 dark:text-theme-300">
                        Showing {{ $savedConversions->firstItem() }} to {{ $savedConversions->lastItem() }} of {{ $savedConversions->total() }} conversions
                    </div>
                    
                    <div class="flex items-center gap-2">
                        {{-- Previous Button --}}
                        @if ($savedConversions->onFirstPage())
                            <button 
                                disabled 
                                class="px-3 py-2 text-sm border border-theme-300 dark:border-theme-600 rounded-md bg-theme-100 dark:bg-theme-800 text-theme-400 dark:text-theme-500 cursor-not-allowed"
                            >
                                Previous
                            </button>
                        @else
                            <button 
                                wire:click="setPage('{{ $savedConversions->previousPageUrl() }}')"
                                class="px-3 py-2 text-sm border border-theme-300 dark:border-theme-600 rounded-md bg-theme-50 dark:bg-theme-700 text-theme-900 dark:text-theme-100 hover:bg-theme-100 dark:hover:bg-theme-600 transition-colors"
                            >
                                Previous
                            </button>
                        @endif
                        
                        {{-- Page Numbers --}}
                        @foreach ($savedConversions->links()->elements[0] as $page)
                            @if ($page['url'])
                                <button 
                                    wire:click="setPage('{{ $page['url'] }}')"
                                    class="px-3 py-2 text-sm border border-theme-300 dark:border-theme-600 rounded-md {{ $page['active'] ? 'bg-theme-300 dark:bg-theme-500 text-theme-900 dark:text-theme-100' : 'bg-theme-50 dark:bg-theme-700 text-theme-900 dark:text-theme-100 hover:bg-theme-100 dark:hover:bg-theme-600' }} transition-colors"
                                >
                                    {{ $page['label'] }}
                                </button>
                            @else
                                <span class="px-3 py-2 text-sm text-theme-500 dark:text-theme-400">
                                    {{ $page['label'] }}
                                </span>
                            @endif
                        @endforeach
                        
                        {{-- Next Button --}}
                        @if ($savedConversions->hasMorePages())
                            <button 
                                wire:click="setPage('{{ $savedConversions->nextPageUrl() }}')"
                                class="px-3 py-2 text-sm border border-theme-300 dark:border-theme-600 rounded-md bg-theme-50 dark:bg-theme-700 text-theme-900 dark:text-theme-100 hover:bg-theme-100 dark:hover:bg-theme-600 transition-colors"
                            >
                                Next
                            </button>
                        @else
                            <button 
                                disabled 
                                class="px-3 py-2 text-sm border border-theme-300 dark:border-theme-600 rounded-md bg-theme-100 dark:bg-theme-800 text-theme-400 dark:text-theme-500 cursor-not-allowed"
                            >
                                Next
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>
    @endauth
</div>
