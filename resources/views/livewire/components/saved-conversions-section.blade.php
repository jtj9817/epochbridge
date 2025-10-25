<?php

use App\Models\SavedConversion;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;

use function Livewire\Volt\computed;
use function Livewire\Volt\on;
use function Livewire\Volt\state;
use function Livewire\Volt\uses;

// Use pagination trait
uses([WithPagination::class]);

state(['perPage' => 10]);

$normalizeConversions = function (?LengthAwarePaginator $paginator) {
    if (! $paginator) {
        return null;
    }

    $items = $paginator->getCollection()->map(function ($item) {
        if (! $item->created_at instanceof Carbon) {
            $item->created_at = Carbon::parse($item->created_at);
        }

        $legacyValues = $item->legacyValues();

        if (empty($item->conversion_data)) {
            $fallbackData = array_filter([
                'gregorian' => $legacyValues['gregorian']
                    ? [
                        'full_date' => $legacyValues['gregorian'],
                        'name' => 'Gregorian',
                        'tags' => ['legacy', 'fallback'],
                    ]
                    : null,
                'jalali' => $legacyValues['jalali']
                    ? [
                        'full_date' => $legacyValues['jalali'],
                        'name' => 'Jalali',
                        'tags' => ['legacy', 'fallback'],
                    ]
                    : null,
                'unix' => $legacyValues['unix'],
                'iso8601' => $legacyValues['iso8601'],
            ], fn ($value) => $value !== null && $value !== '');

            if (! empty($fallbackData)) {
                $item->conversion_data = $fallbackData;
            }
        }

        $item->normalized_conversion_data = $item->conversion_data ?? [];
        $item->legacy_display_values = $legacyValues;
        $item->has_legacy_values = $item->hasLegacyValues();

        return $item;
    });

    return tap($paginator)->setCollection($items);
};

$savedConversions = computed(function () {
    if (! Auth::check()) {
        return null;
    }

    return $this->normalizeConversions(
        SavedConversion::getPaginatedForUser(Auth::id(), $this->perPage)
    );
});

on([
    'conversion-saved' => function () {
        // Refresh computed property by resetting page or forcing re-render
        unset($this->savedConversions);
    },
]);

$deleteConversion = function (int $id) {
    if (Auth::check()) {
        $conversion = SavedConversion::where('user_id', Auth::id())->find($id);
        if ($conversion) {
            $conversion->delete();
            // Refresh computed property
            unset($this->savedConversions);
        }
    }
};

$updatedPerPage = function ($perPage) {
    $this->perPage = $perPage;
    // Reset to first page when changing per-page value
    $this->resetPage();
    // Refresh computed property
    unset($this->savedConversions);
};

$setPage = function ($url) {
    if (Auth::check() && $url) {
        // Extract page number from URL
        $parsedUrl = parse_url($url);
        parse_str($parsedUrl['query'] ?? '', $query);
        $page = $query['page'] ?? 1;

        // Use Livewire's built-in setPage method
        $this->setPage($page);
        // Refresh computed property
        unset($this->savedConversions);
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
        @if ($this->savedConversions && $this->savedConversions->isNotEmpty())
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
                @foreach($this->savedConversions as $item)
                    <x-saved-conversion-item :item="$item" wire:key="auth-{{ $item->id }}" />
                @endforeach
            </div>
            
            <!-- Pagination controls -->
            @if ($this->savedConversions->hasPages())
                <div class="flex items-center justify-between mt-8">
                    <div class="text-sm text-theme-600 dark:text-theme-300">
                        Showing {{ $this->savedConversions->firstItem() }} to {{ $this->savedConversions->lastItem() }} of {{ $this->savedConversions->total() }} conversions
                    </div>
                    
                    <div class="flex items-center gap-2">
                        {{-- Previous Button --}}
                        @if ($this->savedConversions->onFirstPage())
                            <button 
                                disabled 
                                class="px-3 py-2 text-sm border border-theme-300 dark:border-theme-600 rounded-md bg-theme-100 dark:bg-theme-800 text-theme-400 dark:text-theme-500 cursor-not-allowed"
                            >
                                Previous
                            </button>
                        @else
                            <button 
                                wire:click="setPage('{{ $this->savedConversions->previousPageUrl() }}')"
                                class="px-3 py-2 text-sm border border-theme-300 dark:border-theme-600 rounded-md bg-theme-50 dark:bg-theme-700 text-theme-900 dark:text-theme-100 hover:bg-theme-100 dark:hover:bg-theme-600 transition-colors"
                            >
                                Previous
                            </button>
                        @endif
                        
                        {{-- Page Numbers --}}
                        @foreach ($this->savedConversions->links()->elements[0] as $page)
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
                        @if ($this->savedConversions->hasMorePages())
                            <button 
                                wire:click="setPage('{{ $this->savedConversions->nextPageUrl() }}')"
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
