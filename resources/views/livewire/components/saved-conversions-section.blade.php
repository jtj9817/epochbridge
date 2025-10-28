<?php

use App\Models\SavedConversion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\computed;
use function Livewire\Volt\on;

$normalizeConversions = function ($items) {
    if (! $items) {
        return collect([]);
    }

    return $items->map(function ($item) {
        if (! $item->created_at instanceof Carbon) {
            $item->created_at = Carbon::parse($item->created_at);
        }

        $legacyValues = $item->legacyValues();
        $conversionData = is_array($item->conversion_data) ? $item->conversion_data : [];

        $conversionData = collect($conversionData)
            ->reject(function ($data) {
                return is_array($data) && array_key_exists('error', $data);
            })
            ->toArray();

        $technicalFormats = ['unix', 'julian_day', 'julian-day', 'modified_julian_day', 'modified-julian-day', 'mayan'];

        foreach ($technicalFormats as $format) {
            if (! array_key_exists($format, $conversionData)) {
                continue;
            }

            $data = $conversionData[$format];

            if (is_scalar($data)) {
                $conversionData[$format] = [
                    'full_date' => (string) $data,
                    'value' => $data,
                    'name' => $this->formatCalendarName($format),
                ];
            } elseif (is_array($data)) {
                $value = $data['full_date']
                    ?? $data['timestamp']
                    ?? $data['day_number']
                    ?? $data['value']
                    ?? $data['long_count']
                    ?? null;

                if ($value !== null) {
                    $conversionData[$format]['full_date'] = (string) $value;
                }

                $conversionData[$format]['name'] = $conversionData[$format]['name'] ?? $this->formatCalendarName($format);
            }
        }

        if (isset($conversionData['julian_day']) && ! isset($conversionData['julian-day'])) {
            $conversionData['julian-day'] = $conversionData['julian_day'];
        }

        if (isset($conversionData['modified_julian_day']) && ! isset($conversionData['modified-julian-day'])) {
            $conversionData['modified-julian-day'] = $conversionData['modified_julian_day'];
        }

        $usedFallback = false;

        if (empty($conversionData)) {
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
                'unix' => $legacyValues['unix']
                    ? [
                        'full_date' => $legacyValues['unix'],
                        'name' => 'Unix Timestamp',
                        'tags' => ['legacy', 'fallback'],
                    ]
                    : null,
                'iso8601' => $legacyValues['iso8601']
                    ? [
                        'full_date' => $legacyValues['iso8601'],
                        'name' => 'ISO 8601',
                        'tags' => ['legacy', 'fallback'],
                    ]
                    : null,
            ], fn ($value) => $value !== null && $value !== '');

            if (! empty($fallbackData)) {
                $item->legacy_fallback_data = $fallbackData;
                $usedFallback = true;
                $conversionData = $fallbackData;
            } else {
                $item->legacy_fallback_data = [];
            }
        } else {
            $item->legacy_fallback_data = [];
        }

        $item->normalized_conversion_data = $conversionData;
        $item->uses_legacy_fallback = $usedFallback;
        $item->legacy_display_values = $legacyValues;
        $item->has_legacy_values = $item->hasLegacyValues();

        return $item;
    });
};

$formatCalendarName = function (string $key): string {
    $names = [
        'unix' => 'Unix Timestamp',
        'julian_day' => 'Julian Day Number',
        'julian-day' => 'Julian Day Number',
        'modified_julian_day' => 'Modified Julian Day',
        'modified-julian-day' => 'Modified Julian Day',
        'mayan' => 'Mayan Calendar',
    ];

    return $names[$key] ?? ucfirst(str_replace(['_', '-'], ' ', $key));
};

$savedConversions = computed(function () {
    if (! Auth::check()) {
        return collect([]);
    }

    $conversions = SavedConversion::where('user_id', Auth::id())
        ->orderBy('created_at', 'desc')
        ->limit(50)
        ->get();

    return $this->normalizeConversions($conversions);
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

?>

<div class="mt-16 max-w-4xl mx-auto">
    <!-- Saved Conversions for Guests -->
    @guest
    <div
        id="saved-conversions-guest"
        x-data="{
            savedConversions: [],
            collapsedStates: {},
            
            init() {
                this.loadSavedConversions();
                this.loadCollapsedStates();
                this.pruneCollapsedStates();
                this.saveCollapsedStates();

                window.addEventListener('new-guest-conversion', event => {
                    this.savedConversions.unshift(event.detail.conversion);
                    this.savedConversions = this.savedConversions.slice(0, 3);
                    this.saveToLocalStorage();
                    this.pruneCollapsedStates();
                    this.saveCollapsedStates();
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
                } finally {
                    this.pruneCollapsedStates();
                    this.saveCollapsedStates();
                }
            },

            loadCollapsedStates() {
                try {
                    const stored = localStorage.getItem('conversionCollapsedStates');
                    this.collapsedStates = stored ? JSON.parse(stored) : {};
                } catch (e) {
                    console.warn('Failed to load collapsed states:', e);
                    this.collapsedStates = {};
                }
            },

            saveCollapsedStates() {
                try {
                    localStorage.setItem('conversionCollapsedStates', JSON.stringify(this.collapsedStates));
                } catch (e) {
                    console.warn('Failed to persist collapsed states:', e);
                }
            },

            stateKey(item, index) {
                if (item && item.id !== undefined && item.id !== null) {
                    return `id-${item.id}`;
                }
                if (item && item.created_at) {
                    return `created-${item.created_at}`;
                }
                return `index-${index}`;
            },

            isCollapsed(item, index) {
                const key = this.stateKey(item, index);
                return this.collapsedStates[key] !== false;
            },

            pruneCollapsedStates() {
                const activeKeys = new Set(this.savedConversions.map((item, idx) => this.stateKey(item, idx)));

                Object.keys(this.collapsedStates).forEach(key => {
                    if (!activeKeys.has(key)) {
                        delete this.collapsedStates[key];
                    }
                });
            },

            handleCollapsedEvent(detail) {
                if (!detail) {
                    return;
                }

                const { id, index, collapsed } = detail;
                let key = null;

                if (id !== null && id !== undefined && id !== '') {
                    key = `id-${id}`;
                } else if (typeof index === 'number' && index >= 0) {
                    const item = this.savedConversions[index];
                    key = item ? this.stateKey(item, index) : `index-${index}`;
                }

                if (key !== null) {
                    this.collapsedStates[key] = collapsed;
                    this.saveCollapsedStates();
                }
            },
            
            saveToLocalStorage() {
                try {
                    localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions));
                    this.pruneCollapsedStates();
                    this.saveCollapsedStates();
                } catch (e) {
                    console.warn('Failed to save guest conversions:', e);
                    // Handle localStorage quota exceeded
                    if (e.name === 'QuotaExceededError') {
                        // Keep only the most recent conversion
                        this.savedConversions = this.savedConversions.slice(0, 1);
                        localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions));
                        this.pruneCollapsedStates();
                        this.saveCollapsedStates();
                    }
                }
            },
            
            deleteSaved(id) {
                this.savedConversions = this.savedConversions.filter(item => item.id !== id);
                this.saveToLocalStorage();
            }
        }"
        @saved-conversion-collapsed.window="handleCollapsedEvent($event.detail)"
        @delete-guest-conversion.window="deleteSaved($event.detail.id)"
        x-show="savedConversions.length > 0"
        x-cloak
    >
        <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6 text-center sm:text-left">
            Your Saved Conversions
        </h2>
        <div class="space-y-6">
            <template x-for="(item, itemIndex) in savedConversions" :key="item && item.id ? item.id : itemIndex">
                <x-saved-conversion-item
                    :item="null"
                    is-guest
                    ::item-data="item"
                    x-bind:data-collapsed="isCollapsed(item, itemIndex) ? 'true' : 'false'"
                    x-bind:data-initial-collapsed="isCollapsed(item, itemIndex) ? 'true' : 'false'"
                    x-bind:data-index="itemIndex"
                    x-bind:data-conversion-id="item && item.id ? item.id : null"
                />
            </template>
        </div>
    </div>
    @endguest

    <!-- Saved Conversions for Authenticated Users -->
    @auth
    <div id="saved-conversions-auth">
        @if ($this->savedConversions && $this->savedConversions->isNotEmpty())
            <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6 text-center sm:text-left">
                Your Saved Conversions
            </h2>
            
            <div class="space-y-6">
                @foreach($this->savedConversions as $item)
                    <x-saved-conversion-item :item="$item" wire:key="auth-{{ $item->id }}" />
                @endforeach
            </div>
        @endif
    </div>
    @endauth
</div>
