@props(['data'])

<x-calendars.base-calendar-card
    title="Hebrew"
    :copyText="$data['full_date'] ?? ''"
    copyType="hebrew"
    class="bg-purple-50/50 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
    </x-slot:icon>

    <div class="text-sm font-medium text-purple-700 dark:text-purple-300 tracking-wider">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-6xl font-bold text-purple-600 dark:text-purple-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-lg text-purple-600 dark:text-purple-300">
        {{ $data['day_of_week'] ?? 'N/A' }}
    </div>
    <div class="text-sm font-mono text-purple-500 dark:text-purple-500 mt-2">
        {{ $data['year'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
