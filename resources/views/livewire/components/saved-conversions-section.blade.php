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

        // Check if this is a legacy conversion (no conversion_data or empty conversion_data)
        $isLegacyConversion = empty($conversionData) && $item->hasLegacyValues();

        if ($isLegacyConversion) {
            // For legacy conversions, create fallback data immediately
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
                        'name' => 'Unix',
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

            $item->legacy_fallback_data = $fallbackData;
            $item->normalized_conversion_data = $fallbackData;
            $item->uses_legacy_fallback = true;
            $item->legacy_display_values = $legacyValues;
            $item->has_legacy_values = $item->hasLegacyValues();

            return $item;
        }

        // For modern conversions, filter out error entries and normalize technical formats
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

        // If modern conversion has no valid data after filtering errors, check for legacy fallback
        if (empty($conversionData) && $item->hasLegacyValues()) {
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
                        'name' => 'Unix',
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

            $item->legacy_fallback_data = $fallbackData;
            $item->normalized_conversion_data = $fallbackData;
            $item->uses_legacy_fallback = true;
        } else {
            $item->legacy_fallback_data = [];
            $item->normalized_conversion_data = $conversionData;
            $item->uses_legacy_fallback = false;
        }

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
        try {
            $conversion = SavedConversion::where('user_id', Auth::id())->find($id);
            if ($conversion) {
                $conversion->delete();
                // Refresh computed property
                unset($this->savedConversions);
                
                // Dispatch success toast notification to browser
                $this->js("
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: 'Conversion deleted successfully!',
                            type: 'success'
                        }
                    }))
                ");
            } else {
                // Dispatch error toast for not found case
                $this->js("
                    window.dispatchEvent(new CustomEvent('notify', {
                        detail: {
                            message: 'Conversion not found or already deleted.',
                            type: 'error'
                        }
                    }))
                ");
            }
        } catch (\Exception $e) {
            // Dispatch error toast notification to browser
            $this->js("
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: 'Failed to delete conversion. Please try again.',
                        type: 'error'
                    }
                }))
            ");
            
            \Log::error('Failed to delete conversion', [
                'user_id' => Auth::id(),
                'conversion_id' => $id,
                'error' => $e->getMessage()
            ]);
        }
    }
};

$updateNote = function (int $id, string $note) {
    if (!Auth::check()) {
        $this->js("
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: 'You must be logged in to update notes.',
                    type: 'error'
                }
            }))
        ");
        return;
    }
    
    try {
        $note = trim($note);
        
        // Validate note length
        if (strlen($note) > 2048) {
            $this->js("
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: 'Note must not exceed 2048 characters.',
                        type: 'error'
                    }
                }))
            ");
            return;
        }
        
        $conversion = SavedConversion::where('user_id', Auth::id())->find($id);
        
        if ($conversion) {
            $conversion->update(['note' => $note]);
            
            // Refresh computed property
            unset($this->savedConversions);
            
            $this->js("
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: 'Note updated successfully!',
                        type: 'success'
                    }
                }))
            ");
        } else {
            $this->js("
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: 'Conversion not found.',
                        type: 'error'
                    }
                }))
            ");
        }
    } catch (\Exception $e) {
        $this->js("
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: 'Failed to update note. Please try again.',
                    type: 'error'
                }
            }))
        ");
        
        \Log::error('Failed to update conversion note', [
            'user_id' => Auth::id(),
            'conversion_id' => $id,
            'error' => $e->getMessage()
        ]);
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
                try {
                    // Find the conversion before deleting (for better UX)
                    const conversionToDelete = this.savedConversions.find(item => item.id === id);
                    
                    // Filter out the conversion
                    this.savedConversions = this.savedConversions.filter(item => item.id !== id);
                    
                    // Save to localStorage
                    this.saveToLocalStorage();
                    
                    // Dispatch success toast
                    this.$dispatch('notify', {
                        message: 'Conversion deleted successfully!',
                        type: 'success'
                    });
                } catch (error) {
                    console.error('Failed to delete guest conversion:', error);
                    
                    // Error toast
                    this.$dispatch('notify', {
                        message: 'Failed to delete conversion. Please try again.',
                        type: 'error'
                    });
                }
            },

            updateGuestNote(id, note) {
                try {
                    // Find the conversion to update
                    const conversionIndex = this.savedConversions.findIndex(item => item.id === id);
                    
                    if (conversionIndex !== -1) {
                        // Update the note in the conversion
                        this.savedConversions[conversionIndex].note = note;
                        
                        // Save to localStorage
                        this.saveToLocalStorage();
                        
                        return true;
                    }
                    
                    return false;
                } catch (error) {
                    console.error('Failed to update guest note:', error);
                    return false;
                }
            }
        }"
        @saved-conversion-collapsed.window="handleCollapsedEvent($event.detail)"
        @delete-guest-conversion.window="deleteSaved($event.detail.id)"
        @update-guest-note.window="updateGuestNote($event.detail.id, $event.detail.note)"
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
                    x-bind:data-item-json="JSON.stringify(item)"
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
