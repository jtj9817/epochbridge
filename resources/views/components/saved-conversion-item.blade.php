@props(['item', 'isGuest' => false])

@php
    // Get conversion data based on whether it's guest or authenticated
    $conversionData = $isGuest
        ? $item['conversion_data'] ?? []
        : ($item->normalized_conversion_data ?? $item->conversion_data ?? []);
    $originalInput = $isGuest ? ($item['original_input'] ?? '') : ($item->original_input ?? '');
    $createdAt = $isGuest ? ($item['created_at'] ?? '') : ($item->created_at ?? null);
@endphp

<div
    x-data="{
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
            const data = {{ $isGuest ? 'item.conversion_data' : 'this.getAllData()' }};
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
                    if (!cardData.error) {
                        const name = cardData.name || system;
                        const date = cardData.full_date || (typeof cardData === 'string' ? cardData : 'N/A');
                        const dow = cardData.day_of_week || '';
                        text += `${name}: ${date}${dow ? ' (' + dow + ')' : ''}\n`;
                    }
                });
            }

            return text;
        },

        // Get all data for authenticated users
        getAllData() {
            return @json($conversionData ?? []);
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
    class="bg-white dark:bg-theme-800 rounded-xl shadow-lg border border-theme-200 dark:border-theme-700 overflow-hidden"
>
    <!-- Header with centered input date -->
    <div class="bg-[#50493E] p-4 border-b border-theme-200 dark:border-theme-700">
        <div class="flex items-center justify-between">
            <div class="flex-grow text-center">
                <div class="text-base text-white mb-1">Input Date</div>
                <div class="text-sm font-bold text-white font-mono">
                    {{ $originalInput }}
                </div>
                <div class="text-xl text-white/80 mt-1 font-mono">
                    @if($isGuest)
                        Saved: {{ \Carbon\Carbon::parse($createdAt)->format('M j, Y g:i A') }}
                    @else
                        Saved: {{ $createdAt?->format('M j, Y g:i A') }}
                    @endif
                </div>
            </div>
            
            <!-- Action buttons container -->
            <div class="flex items-center gap-2">
                <!-- Copy All Button -->
                <button
                    @click="copyAll()"
                    class="text-blue-500 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300 transition-colors p-2 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20"
                    title="Copy all conversions"
                    aria-label="Copy all calendar conversions to clipboard"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                </button>

                <!-- Delete button -->
                @if($isGuest)
                    <button @click="deleteSaved(item.id)" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20" title="Delete conversion">
                        <span class="sr-only">Delete</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                @else
                    <button wire:click="deleteConversion({{ $item->id }})" wire:confirm="Are you sure you want to delete this saved conversion?" class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20" title="Delete conversion">
                        <span class="sr-only">Delete</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Calendar systems table -->
    @if($isGuest)
        <!-- Client-side rendering for guest users (data in localStorage/Alpine.js) -->
        <div x-show="item.conversion_data && Object.keys(item.conversion_data).length > 0" class="overflow-x-auto bg-[#13100B]">
            <table class="w-full">
                <tbody class="divide-y divide-[#50493E]">
                    <template x-for="[system, cardData] in Object.entries(item.conversion_data)" :key="system">
                        <tr class="hover:bg-[#50493E]/30 transition-colors">
                            <td class="px-4 py-2 w-1/3">
                                <span class="font-bold text-[#F5D2A5]" x-text="cardData.name || system"></span>
                            </td>
                            <td class="px-4 py-2 w-1/3 text-[#CDB586]">
                                <template x-if="cardData.full_date">
                                    <span x-text="cardData.full_date"></span>
                                </template>
                                <template x-if="!cardData.full_date && typeof cardData === 'object'">
                                    <span class="text-[#7E7254]">Complex data</span>
                                </template>
                                <template x-if="!cardData.full_date && typeof cardData !== 'object'">
                                    <span x-text="cardData"></span>
                                </template>
                            </td>
                            <td class="px-4 py-2 w-1/3 text-sm text-[#7E7254]">
                                <template x-if="cardData.day_of_week">
                                    <span x-text="cardData.day_of_week"></span>
                                </template>
                            </td>
                            <!-- Copy button column -->
                            <td class="px-4 py-2 w-auto text-right">
                                <button
                                    @click="copyValue(cardData.full_date || cardData, cardData.name || system)"
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
        
        <!-- Fallback for guest users with missing/empty data -->
        <div x-show="!item.conversion_data || Object.keys(item.conversion_data).length === 0" class="p-4 bg-[#13100B]">
            <p class="text-center text-[#7E7254]">No conversion data available</p>
        </div>
    @else
        <!-- Server-side rendering for authenticated users -->
        @if(!empty($conversionData))
            <div class="overflow-x-auto bg-[#13100B]">
                <table class="w-full">
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
                                        @elseif(is_scalar($data))
                                            {{ $data }}
                                        @else
                                            <span class="text-[#7E7254]">Complex data</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 w-1/3 text-sm text-[#7E7254]">
                                        {{ $data['day_of_week'] ?? '' }}
                                    </td>
                                    <!-- Copy button column -->
                                    <td class="px-4 py-2 w-auto text-right">
                                        <button
                                            @click="copyValue('{{ $data['full_date'] ?? (is_scalar($data) ? $data : 'N/A') }}', '{{ $data['name'] ?? ucfirst(str_replace(['_', '-'], ' ', $system)) }}')"
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
            <!-- Fallback for authenticated users with old format or missing data -->
            <div class="p-4 bg-[#13100B]">
                @php
                    $legacyValues = $item->legacy_display_values ?? $item->legacyValues();
                    $hasLegacyData = $item->has_legacy_values ?? $item->hasLegacyValues();
                @endphp

                @if($hasLegacyData)
                    <table class="w-full">
                        <tbody class="divide-y divide-[#50493E]">
                            @if($legacyValues['gregorian'])
                                <tr class="hover:bg-[#50493E]/30">
                                    <td class="px-4 py-2 w-1/3"><span class="font-bold text-[#F5D2A5]">Gregorian</span></td>
                                    <td class="px-4 py-2 w-2/3 text-[#CDB586]">{{ $legacyValues['gregorian'] }}</td>
                                </tr>
                            @endif
                            @if($legacyValues['jalali'])
                                <tr class="hover:bg-[#50493E]/30">
                                    <td class="px-4 py-2 w-1/3"><span class="font-bold text-[#F5D2A5]">Jalali</span></td>
                                    <td class="px-4 py-2 w-2/3 text-[#CDB586]">{{ $legacyValues['jalali'] }}</td>
                                </tr>
                            @endif
                            @if($legacyValues['unix'])
                                <tr class="hover:bg-[#50493E]/30">
                                    <td class="px-4 py-2 w-1/3"><span class="font-bold text-[#F5D2A5]">Unix</span></td>
                                    <td class="px-4 py-2 w-2/3 text-[#CDB586]">{{ $legacyValues['unix'] }}</td>
                                </tr>
                            @endif
                            @if($legacyValues['iso8601'])
                                <tr class="hover:bg-[#50493E]/30">
                                    <td class="px-4 py-2 w-1/3"><span class="font-bold text-[#F5D2A5]">ISO 8601</span></td>
                                    <td class="px-4 py-2 w-2/3 text-[#CDB586]">{{ $legacyValues['iso8601'] }}</td>
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
