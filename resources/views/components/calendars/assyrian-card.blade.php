@props(['data'])

<x-calendars.base-calendar-card
    title="Assyrian"
    copyType="assyrian"
    :disclaimer="$data['disclaimer'] ?? null"
    class="calendar-assyrian"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-lime-700 dark:text-lime-300 uppercase tracking-widest">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-5xl font-bold text-lime-600 dark:text-lime-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-xs font-mono text-lime-500 dark:text-lime-500 mt-2">
        {{ $data['year'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
