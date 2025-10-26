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
        $conversionData = $item->conversion_data ?? [];

        $technicalFormats = ['unix', 'julian_day', 'julian-day', 'modified_julian_day', 'modified-julian-day', 'mayan'];

        foreach ($technicalFormats as $format) {
            if (! isset($conversionData[$format])) {
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
                $conversionData = $fallbackData;
            }
        }

        $item->normalized_conversion_data = $conversionData;
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
        @delete-guest-conversion.window="deleteSaved($event.detail.id)"
        x-show="savedConversions.length > 0"
        x-cloak
    >
        <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6 text-center sm:text-left">
            Your Saved Conversions
        </h2>
        <div class="space-y-6">
            <template x-for="item in savedConversions" :key="item.id">
                <x-saved-conversion-item :item="null" is-guest ::item-data="item" />
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
