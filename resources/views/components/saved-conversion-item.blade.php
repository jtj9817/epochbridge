@props(['item', 'isGuest' => false])

@php
    // Get conversion data based on whether it's guest or authenticated
    $conversionData = $isGuest ? $item['conversion_data'] ?? [] : ($item->conversion_data ?? []);
    $originalInput = $isGuest ? ($item['original_input'] ?? '') : $item->original_input;
    $createdAt = $isGuest ? ($item['created_at'] ?? '') : $item->created_at;
    
    // Define calendar card components mapping
    $calendarComponents = [
        'gregorian' => 'calendars.gregorian-card',
        'jalali' => 'calendars.jalali-card',
        'unix' => 'calendars.unix-card',
        'iso8601' => 'calendars.iso8601-card',
        'julian-day' => 'calendars.julian-day-card',
        'modified-julian-day' => 'calendars.modified-julian-day-card',
        'hebrew' => 'calendars.hebrew-card',
        'islamic' => 'calendars.islamic-card',
        'french_republican' => 'calendars.french-republican-card',
        'chinese' => 'calendars.chinese-card',
        'japanese' => 'calendars.japanese-card',
        'babylonian' => 'calendars.babylonian-card',
        'assyrian' => 'calendars.assyrian-card',
        'macedonian' => 'calendars.macedonian-card',
        'coptic' => 'calendars.coptic-card',
        'egyptian' => 'calendars.egyptian-card',
        'ethiopian' => 'calendars.ethiopian-card',
        'mayan' => 'calendars.mayan-card',
        'julian' => 'calendars.julian-card',
    ];
@endphp

<div class="bg-theme-100 dark:bg-theme-500 rounded-xl shadow-lg border border-theme-300 dark:border-theme-300 overflow-hidden">
    <!-- Header with original input and actions -->
    <div class="p-4 border-b border-theme-300/50 dark:border-theme-300/50 flex items-center justify-between">
        <div class="flex-grow">
            <div class="flex items-center gap-3 mb-1">
                <span class="text-sm font-semibold text-theme-700 dark:text-theme-200">Input:</span>
                <code class="px-2 py-1 bg-theme-200 dark:bg-theme-600 rounded text-sm font-mono text-theme-800 dark:text-theme-100">
                    {{ $originalInput }}
                </code>
            </div>
            <div class="text-xs text-theme-600 dark:text-theme-300">
                @if($isGuest)
                    Saved: {{ \Carbon\Carbon::parse($createdAt)->format('M j, Y g:i A') }}
                @else
                    Saved: {{ $createdAt->format('M j, Y g:i A') }}
                @endif
            </div>
        </div>
        
        <!-- Delete button -->
        @if($isGuest)
            <button @click="deleteSaved(item.id)" class="text-red-500 hover:text-red-700 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20" title="Delete conversion">
                <span class="sr-only">Delete</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        @else
            <button wire:click="deleteConversion({{ $item->id }})" wire:confirm="Are you sure you want to delete this saved conversion?" class="text-red-500 hover:text-red-700 transition-colors p-2 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20" title="Delete conversion">
                <span class="sr-only">Delete</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        @endif
    </div>

    <!-- Calendar systems grid -->
    @if(!empty($conversionData))
        <div class="p-4">
            <div class="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($conversionData as $system => $data)
                    @if(!isset($data['error']) && isset($calendarComponents[$system]))
                        <div class="transform transition-transform hover:scale-[1.02]">
                            @if($isGuest)
                                <!-- For guest items, we need to render the card with Alpine.js data binding -->
                                <div x-data="{ calendarData: {{ json_encode($data) }} }">
                                    @include($calendarComponents[$system], ['data' => $data])
                                </div>
                            @else
                                @include($calendarComponents[$system], ['data' => $data])
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    @else
        <!-- Fallback for old format or missing data -->
        <div class="p-4">
            <div class="text-sm text-theme-600 dark:text-theme-300 grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-2">
                @if($isGuest)
                    <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Gregorian:</strong> <span x-text="item.gregorian?.full_date || 'N/A'"></span></div>
                    <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Jalali:</strong> <span x-text="item.jalali?.full_date || 'N/A'"></span></div>
                    <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Unix:</strong> <span x-text="item.unix || 'N/A'"></span></div>
                @else
                    <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Gregorian:</strong> <span>{{ $item->gregorian_date?->format('Y-m-d') ?? 'N/A' }}</span></div>
                    <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Jalali:</strong> <span>{{ $item->jalali_date ?? 'N/A' }}</span></div>
                    <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Unix:</strong> <span>{{ $item->unix_timestamp ?? 'N/A' }}</span></div>
                @endif
            </div>
        </div>
    @endif
</div>