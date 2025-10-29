@props([
    'item',
    'isGuest' => false,
    'itemData' => null,
    'initiallyCollapsed' => true,
    'index' => null,
    'conversionKey' => null,
])

@php
    $conversionData = $isGuest
        ? $item['conversion_data'] ?? []
        : ($item->normalized_conversion_data ?? $item->conversion_data ?? []);

    $originalInput = $isGuest ? ($item['original_input'] ?? '') : ($item->original_input ?? '');
    $createdAt = $isGuest ? ($item['created_at'] ?? '') : ($item->created_at ?? null);

    $formattedTimestamp = null;

    if ($createdAt) {
        $formattedTimestamp = $isGuest
            ? \Carbon\Carbon::parse($createdAt)->format('M j, Y g:i A')
            : $createdAt?->format('M j, Y g:i A');
    }

    $usesLegacyFallback = $isGuest ? false : (bool) ($item->uses_legacy_fallback ?? false);
    $legacyFallbackData = $isGuest ? [] : ($item->legacy_fallback_data ?? []);
    $legacyValues = $isGuest ? [] : ($item->legacy_display_values ?? $item->legacyValues());
    $hasLegacyValues = $isGuest ? false : ($item->has_legacy_values ?? $item->hasLegacyValues());

    if ($usesLegacyFallback) {
        $conversionData = $legacyFallbackData ?? [];
    }

    $hasConversionTableData = ! $usesLegacyFallback && ! empty($conversionData);

    $contentId = uniqid('saved-conversion-content-');
    $initialCollapsedValue = $initiallyCollapsed ? 'true' : 'false';
    $cardIndex = $index !== null ? (string) $index : null;
    $resolvedConversionKey = $conversionKey
        ?? ($isGuest ? ($item['id'] ?? null) : ($item->id ?? null));
    $conversionKeyValue = $resolvedConversionKey !== null ? (string) $resolvedConversionKey : null;
    $uniqueIdSuffix = $conversionKeyValue ?? $cardIndex ?? 'guest';
@endphp

<div
    id="saved-conversion-item-{{ $uniqueIdSuffix }}"
    @if(!$isGuest && !empty($legacyValues['jalali']))
        data-legacy-jalali="{{ str_replace('-', '/', $legacyValues['jalali']) }}"
    @endif
    x-data="{
        collapsed: true,
        @if($isGuest)
        itemData: {},
        @endif

        init() {
            @if($isGuest)
            // Use $nextTick to ensure Alpine bindings are applied
            this.$nextTick(() => {
                const itemJson = this.$el.dataset.itemJson;
                if (itemJson) {
                    try {
                        this.itemData = JSON.parse(itemJson);
                    } catch (e) {
                        console.error('Failed to parse item data:', e);
                        this.itemData = {};
                    }
                }
            });
            @endif

            const attr = this.$el.getAttribute('data-collapsed');
            const datasetValue = this.$el.dataset.initialCollapsed;

            if (attr !== null) {
                this.collapsed = attr !== 'false';
            } else if (datasetValue !== undefined) {
                this.collapsed = datasetValue !== 'false';
            }

            this.syncCollapsedAttr();
        },

        toggleCollapsed() {
            this.collapsed = !this.collapsed;
            this.syncCollapsedAttr();

            const hasIndex = this.$el.dataset.index !== undefined;
            const indexValue = hasIndex ? Number(this.$el.dataset.index) : null;
            const conversionId = this.$el.dataset.conversionId ?? null;

            if (hasIndex || conversionId !== null) {
                this.$dispatch('saved-conversion-collapsed', {
                    index: indexValue,
                    id: conversionId,
                    collapsed: this.collapsed
                });
            }
        },

        syncCollapsedAttr() {
            const value = this.collapsed ? 'true' : 'false';
            this.$el.setAttribute('data-collapsed', value);
            this.$el.dataset.collapsed = value;
            this.$el.dataset.initialCollapsed = value;
        },

        // Copy individual calendar value
        copyValue(value, calendarName) {
            if (!navigator.clipboard) {
                this.showFallbackCopy(value);
                return;
            }

            navigator.clipboard.writeText(value)
                .then(() => {
                    this.showCopySuccess(calendarName);
                })
                .catch((err) => {
                    console.error('Copy failed:', err);
                    this.showCopyError();
                });
        },

        // Copy all calendar systems
        copyAll() {
            @if($isGuest)
            // For guest users, use itemData bound from parent
            const data = this.itemData?.conversion_data || {};
            @else
            // For authenticated users, use server-side data through component method
            const data = this.getAllData();
            @endif
            const formatted = this.formatAllData(data);

            if (!navigator.clipboard) {
                this.showFallbackCopy(formatted);
                return;
            }

            navigator.clipboard.writeText(formatted)
                .then(() => {
                    this.showCopySuccess('all calendars');
                })
                .catch((err) => {
                    console.error('Copy failed:', err);
                    this.showCopyError();
                });
        },

        // Format all data for copying
        formatAllData(data) {
            let text = 'Input Date: {{ $originalInput }}\n\n';

            if (typeof data === 'object' && data !== null) {
                Object.entries(data).forEach(([system, cardData]) => {
                    if (!cardData?.error) {
                        const name = cardData?.name || system;
                        const isObject = typeof cardData === 'object' && cardData !== null;
                        const date = isObject
                            ? cardData.full_date
                                || cardData.value
                                || cardData.timestamp
                                || cardData.day_number
                                || cardData.long_count
                                || 'Unavailable'
                            : (cardData ?? 'Unavailable');
                        const dow = isObject ? (cardData.day_of_week || '') : '';
                        text += `${name}: ${date}${dow ? ' (' + dow + ')' : ''}\n`;
                    }
                });
            }

            return text;
        },

        // Get all data for both guest and authenticated users
        getAllData() {
            @if($isGuest)
            // For guest users, use itemData bound from parent
            return this.itemData?.conversion_data || {};
            @else
            // For authenticated users, return server-side data
            return {{ Js::from($usesLegacyFallback ? ($legacyFallbackData ?? []) : ($conversionData ?? [])) }};
            @endif
        },

        // Show success notification
        showCopySuccess(itemName) {
            // Use Alpine toast or temporary button state
            this.$dispatch('notify', {
                message: `Copied ${itemName} to clipboard!`,
                type: 'success'
            });
        },

        // Show error notification
        showCopyError() {
            this.$dispatch('notify', {
                message: 'Failed to copy. Please try again.',
                type: 'error'
            });
        },

        // Fallback for older browsers
        showFallbackCopy(text) {
            alert('Please manually copy: ' + text);
        }
    }"
    data-initial-collapsed="{{ $initialCollapsedValue }}"
    data-collapsed="{{ $initialCollapsedValue }}"
    @if($cardIndex !== null)
        data-index="{{ $cardIndex }}"
    @endif
    @if($conversionKeyValue !== null)
        data-conversion-id="{{ $conversionKeyValue }}"
    @endif
    {{ $attributes->class('bg-white dark:bg-theme-800 rounded-xl shadow-lg border border-theme-200 dark:border-theme-700 overflow-hidden transition-colors') }}
>
    <!-- Collapsible header -->
    <div
        id="saved-conversion-header-{{ $uniqueIdSuffix }}"
        class="bg-[#50493E] p-1 border-b border-theme-200 dark:border-theme-700 cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 flex items-center justify-between gap-4"
        role="button"
        tabindex="0"
        x-on:click="toggleCollapsed()"
        x-on:keydown.enter.prevent="toggleCollapsed()"
        x-on:keydown.space.prevent="toggleCollapsed()"
        :aria-expanded="(!collapsed).toString()"
        aria-controls="{{ $contentId }}"
    >
        <div class="flex-1 min-w-0">
            <p id="saved-conversion-label-input-{{ $uniqueIdSuffix }}" class="text-xs font-medium uppercase tracking-wide text-white/70">
                Input Date
            </p>
            <p id="saved-conversion-value-input-{{ $uniqueIdSuffix }}" class="mt-1 font-semibold text-white truncate">
                {{ $originalInput }}
            </p>
            @if($formattedTimestamp)
                <p id="saved-conversion-timestamp-{{ $uniqueIdSuffix }}" class="mt-1 text-sm text-white/60">
                    Saved: {{ $formattedTimestamp }}
                </p>
            @endif
        </div>

        <div class="shrink-1 mx-2 text-white/80" aria-hidden="true">
            <svg
                id="saved-conversion-chevron-{{ $uniqueIdSuffix }}"
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="3.0"
                class="w-5 h-5 transition-transform duration-200 ease-in-out color-white"
                :class="{ 'rotate-180': !collapsed }"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
            </svg>
        </div>

        <div class="flex items-center gap-2" x-on:click.stop>
                <button
                    id="saved-conversion-btn-copy-all-{{ $uniqueIdSuffix }}"
                    x-on:click.stop="copyAll()"
                    class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                    title="Copy all conversions"
                    aria-label="Copy all calendar conversions to clipboard"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>

                @if($isGuest)
                    <button
                        id="saved-conversion-btn-delete-{{ $uniqueIdSuffix }}"
                        x-on:click.stop="
                            if (confirm('Are you sure you want to delete this saved conversion?')) {
                                $dispatch('delete-guest-conversion', { id: itemData.id })
                            }
                        "
                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                        title="Delete conversion"
                    >
                        <span class="sr-only">Delete</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                @else
                    <button
                        id="saved-conversion-btn-delete-{{ $uniqueIdSuffix }}"
                        wire:click="deleteConversion({{ $item->id }})"
                        wire:confirm="Are you sure you want to delete this saved conversion?"
                        class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500"
                        title="Delete conversion"
                    >
                        <span class="sr-only">Delete</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                @endif
        </div>
    </div>

    <div
        id="{{ $contentId }}"
        x-show="!collapsed"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        x-cloak
        class="border-t border-theme-200 dark:border-theme-700"
    >
        @if($isGuest)
            <div x-show="itemData.conversion_data && Object.keys(itemData.conversion_data || {}).length > 0" class="overflow-x-auto bg-[#13100B]">
                <table id="saved-conversion-table-guest-{{ $uniqueIdSuffix }}" class="w-full">
                    <tbody class="divide-y divide-[#50493E]">
                        <template x-for="[system, cardData] in Object.entries(itemData.conversion_data || {})" :key="system">
                            <tr class="hover:bg-[#50493E]/30 transition-colors">
                                <td class="px-4 py-2 w-1/3">
                                    <span class="font-bold text-[#F5D2A5]" x-text="cardData.name || system"></span>
                                </td>
                                <td class="px-4 py-2 w-1/3 text-[#CDB586]">
                                    <template x-if="cardData.full_date">
                                        <span x-text="cardData.full_date"></span>
                                    </template>
                                    <template x-if="!cardData.full_date && cardData.value">
                                        <span x-text="cardData.value"></span>
                                    </template>
                                    <template x-if="!cardData.full_date && !cardData.value && cardData.timestamp">
                                        <span x-text="cardData.timestamp"></span>
                                    </template>
                                    <template x-if="!cardData.full_date && !cardData.value && !cardData.timestamp && cardData.day_number">
                                        <span x-text="cardData.day_number"></span>
                                    </template>
                                    <template x-if="!cardData.full_date && !cardData.value && !cardData.timestamp && !cardData.day_number && cardData.long_count">
                                        <span x-text="cardData.long_count"></span>
                                    </template>
                                    <template x-if="!cardData.full_date && !cardData.value && !cardData.timestamp && !cardData.day_number && !cardData.long_count && typeof cardData !== 'object'">
                                        <span x-text="cardData"></span>
                                    </template>
                                    <template x-if="!cardData.full_date && !cardData.value && !cardData.timestamp && !cardData.day_number && !cardData.long_count && typeof cardData === 'object'">
                                        <span class="text-[#7E7254]">N/A</span>
                                    </template>
                                </td>
                                <td class="px-4 py-2 w-1/3 text-sm text-[#7E7254]">
                                    <template x-if="cardData.day_of_week">
                                        <span x-text="cardData.day_of_week"></span>
                                    </template>
                                </td>
                                <td class="px-4 py-2 w-auto text-right">
                                    <button
                                        x-on:click="copyValue(cardData.full_date || cardData.value || cardData.timestamp || cardData.day_number || cardData.long_count || cardData, cardData.name || system)"
                                        class="text-theme-400 hover:text-theme-600 dark:text-theme-500 dark:hover:text-theme-300 transition-colors p-1.5 rounded hover:bg-theme-100 dark:hover:bg-theme-700/30"
                                        title="Copy value"
                                        aria-label="Copy calendar value to clipboard"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="!itemData.conversion_data || Object.keys(itemData.conversion_data || {}).length === 0" class="p-4 bg-[#13100B]">
                <p class="text-center text-[#7E7254]">No conversion data available</p>
            </div>
        @else
            @if($hasConversionTableData)
                <div class="overflow-x-auto bg-[#13100B]">
                    <table id="saved-conversion-table-auth-{{ $item->id }}" class="w-full">
                        <tbody class="divide-y divide-[#50493E]">
                            @foreach($conversionData as $system => $data)
                                @if(!isset($data['error']))
                                    <tr class="hover:bg-[#50493E]/30 transition-colors">
                                        <td class="px-4 py-2 w-1/3">
                                            <span class="font-bold text-[#F5D2A5]">{{ $data['name'] ?? ucfirst(str_replace(['_', '-'], ' ', $system)) }}</span>
                                        </td>
                                        <td class="px-4 py-2 w-1/3 text-[#CDB586]">
                                            @if(isset($data['full_date']))
                                                {{ $data['full_date'] }}
                                            @elseif(isset($data['value']))
                                                {{ $data['value'] }}
                                            @elseif(isset($data['timestamp']))
                                                {{ $data['timestamp'] }}
                                            @elseif(isset($data['day_number']))
                                                {{ $data['day_number'] }}
                                            @elseif(isset($data['long_count']))
                                                {{ $data['long_count'] }}
                                            @elseif(is_scalar($data))
                                                {{ $data }}
                                            @else
                                                <span class="text-[#7E7254]">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 w-1/3 text-sm text-[#7E7254]">
                                            {{ $data['day_of_week'] ?? '' }}
                                        </td>
                                        <td class="px-4 py-2 w-auto text-right">
                                            <button
                                                x-on:click="copyValue('{{ $data['full_date'] ?? $data['value'] ?? $data['timestamp'] ?? $data['day_number'] ?? $data['long_count'] ?? (is_scalar($data) ? $data : 'N/A') }}', '{{ $data['name'] ?? ucfirst(str_replace(['_', '-'], ' ', $system)) }}')"
                                                class="text-theme-400 hover:text-theme-600 dark:text-theme-500 dark:hover:text-theme-300 transition-colors p-1.5 rounded hover:bg-theme-100 dark:hover:bg-theme-700/30"
                                                title="Copy value"
                                                aria-label="Copy calendar value to clipboard"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-4 bg-[#13100B]">
                    @if(!empty($legacyFallbackData))
                        <table id="saved-conversion-table-legacy-{{ $item->id }}" class="w-full">
                            <tbody class="divide-y divide-[#50493E]">
                                @foreach($legacyFallbackData as $system => $data)
                                    @php
                                        $label = $data['name'] ?? ucfirst(str_replace(['_', '-'], ' ', $system));
                                        $value = $legacyValues[$system] ?? ($data['full_date'] ?? $data['value'] ?? null);
                                        if ($system === 'jalali' && is_string($value) && ! str_contains($value, '/')) {
                                            $value = str_replace('-', '/', $value);
                                        }
                                    @endphp
                                    @if($value)
                                        <tr class="hover:bg-[#50493E]/30 transition-colors">
                                            <td class="px-4 py-2 w-1/3"><span class="font-bold text-[#F5D2A5]">{{ $label }}:</span></td>
                                            <td class="px-4 py-2 w-1/3 text-[#CDB586]">{{ $value }}</td>
                                            <td class="px-4 py-2 w-1/3 text-[#7E7254]">{{ $data['day_of_week'] ?? '' }}</td>
                                            <td class="px-4 py-2 w-auto text-right">
                                                <button
                                                    x-on:click="copyValue('{{ $value }}', '{{ $label }}')"
                                                    class="text-theme-400 hover:text-theme-600 dark:text-theme-500 dark:hover:text-theme-300 transition-colors p-1.5 rounded hover:bg-theme-100 dark:hover:bg-theme-700/30"
                                                    title="Copy value"
                                                    aria-label="Copy {{ $label }} calendar value to clipboard"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    @elseif($hasLegacyValues)
                        <table id="saved-conversion-table-legacy-{{ $item->id }}" class="w-full">
                            <tbody class="divide-y divide-[#50493E]">
                                @if($legacyValues['gregorian'])
                                    <tr class="hover:bg-[#50493E]/30 transition-colors">
                                        <td class="px-4 py-2 w-1/3"><span class="font-bold text-[#F5D2A5]">Gregorian:</span></td>
                                        <td class="px-4 py-2 w-1/3 text-[#CDB586]">{{ $legacyValues['gregorian'] }}</td>
                                        <td class="px-4 py-2 w-1/3"></td>
                                        <td class="px-4 py-2 w-auto text-right">
                                            <button
                                                x-on:click="copyValue('{{ $legacyValues['gregorian'] }}', 'Gregorian')"
                                                class="text-theme-400 hover:text-theme-600 dark:text-theme-500 dark:hover:text-theme-300 transition-colors p-1.5 rounded hover:bg-theme-100 dark:hover:bg-theme-700/30"
                                                title="Copy value"
                                                aria-label="Copy Gregorian calendar value to clipboard"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                                @if($legacyValues['jalali'])
                                    <tr class="hover:bg-[#50493E]/30 transition-colors">
                                        <td class="px-4 py-2 w-1/3"><span class="font-bold text-[#F5D2A5]">Jalali:</span></td>
                                        <td class="px-4 py-2 w-1/3 text-[#CDB586]">{{ str_replace('-', '/', $legacyValues['jalali']) }}</td>
                                        <td class="px-4 py-2 w-1/3"></td>
                                        <td class="px-4 py-2 w-auto text-right">
                                            <button
                                                x-on:click="copyValue('{{ str_replace('-', '/', $legacyValues['jalali']) }}', 'Jalali')"
                                                class="text-theme-400 hover:text-theme-600 dark:text-theme-500 dark:hover:text-theme-300 transition-colors p-1.5 rounded hover:bg-theme-100 dark:hover:bg-theme-700/30"
                                                title="Copy value"
                                                aria-label="Copy Jalali calendar value to clipboard"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                                @if($legacyValues['unix'])
                                    <tr class="hover:bg-[#50493E]/30 transition-colors">
                                        <td class="px-4 py-2 w-1/3"><span class="font-bold text-[#F5D2A5]">Unix:</span></td>
                                        <td class="px-4 py-2 w-1/3 text-[#CDB586]">{{ $legacyValues['unix'] }}</td>
                                        <td class="px-4 py-2 w-1/3"></td>
                                        <td class="px-4 py-2 w-auto text-right">
                                            <button
                                                x-on:click="copyValue('{{ $legacyValues['unix'] }}', 'Unix')"
                                                class="text-theme-400 hover:text-theme-600 dark:text-theme-500 dark:hover:text-theme-300 transition-colors p-1.5 rounded hover:bg-theme-100 dark:hover:bg-theme-700/30"
                                                title="Copy value"
                                                aria-label="Copy Unix timestamp to clipboard"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                                @if($legacyValues['iso8601'])
                                    <tr class="hover:bg-[#50493E]/30 transition-colors">
                                        <td class="px-4 py-2 w-1/3"><span class="font-bold text-[#F5D2A5]">ISO 8601:</span></td>
                                        <td class="px-4 py-2 w-1/3 text-[#CDB586]">{{ $legacyValues['iso8601'] }}</td>
                                        <td class="px-4 py-2 w-1/3"></td>
                                        <td class="px-4 py-2 w-auto text-right">
                                            <button
                                                x-on:click="copyValue('{{ $legacyValues['iso8601'] }}', 'ISO 8601')"
                                                class="text-theme-400 hover:text-theme-600 dark:text-theme-500 dark:hover:text-theme-300 transition-colors p-1.5 rounded hover:bg-theme-100 dark:hover:bg-theme-700/30"
                                                title="Copy value"
                                                aria-label="Copy ISO 8601 date to clipboard"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    @else
                        <p class="text-center text-[#7E7254]">No conversion data available</p>
                    @endif
                </div>
            @endif
        @endif
    </div>
</div>
