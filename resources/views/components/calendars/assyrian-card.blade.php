@props(['data'])

<x-calendars.base-calendar-card
    title="Assyrian"
    copyType="assyrian"
    :disclaimer="$data['disclaimer'] ?? null"
    class="bg-gray-50/50 dark:bg-gray-950/30 border-gray-200 dark:border-gray-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
        </svg>
    </x-slot:icon>

    <div class="text-sm font-medium text-gray-700 dark:text-gray-300 tracking-wider">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-6xl font-bold text-gray-600 dark:text-gray-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-sm font-mono text-gray-500 dark:text-gray-500 mt-2">
        {{ $data['year'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
