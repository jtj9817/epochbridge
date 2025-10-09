@props(['item', 'isGuest' => false])

<div class="p-4 bg-theme-100 dark:bg-theme-500 rounded-lg shadow-md flex items-center justify-between gap-4">
    <div class="text-sm text-theme-900 dark:text-theme-50 grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-2 text-center sm:text-left flex-grow">
        @if($isGuest)
            <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Gregorian:</strong> <span x-text="item.gregorian.full_date"></span></div>
            <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Jalali:</strong> <span x-text="item.jalali.full_date"></span></div>
            <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Unix:</strong> <span x-text="item.unix"></span></div>
        @else
            <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Gregorian:</strong> <span>{{ $item->gregorian_date }}</span></div>
            <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Jalali:</strong> <span>{{ $item->jalali_date }}</span></div>
            <div class="font-mono"><strong class="font-semibold text-theme-500 dark:text-theme-100">Unix:</strong> <span>{{ $item->unix_timestamp }}</span></div>
        @endif
    </div>
    @if($isGuest)
        <button @click="deleteSaved(item.id)" class="text-red-500 hover:text-red-700 transition-colors flex-shrink-0" title="Delete">
            <span class="sr-only">Delete</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    @else
        <button wire:click="deleteConversion({{ $item->id }})" wire:confirm="Are you sure you want to delete this saved conversion?" class="text-red-500 hover:text-red-700 transition-colors flex-shrink-0" title="Delete">
            <span class="sr-only">Delete</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
        </button>
    @endif
</div>
