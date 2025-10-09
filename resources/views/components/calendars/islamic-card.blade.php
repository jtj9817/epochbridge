@props(['data'])

<x-calendars.base-calendar-card
    title="Islamic"
    :copyText="$data['full_date'] ?? ''"
    copyType="islamic"
    class="bg-green-50/50 dark:bg-green-950/30 border-green-200 dark:border-green-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9v-9m0-9v9" />
        </svg>
    </x-slot:icon>

    <div class="text-sm font-medium text-green-700 dark:text-green-300 tracking-wider">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-6xl font-bold text-green-600 dark:text-green-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-lg text-green-600 dark:text-green-300">
        {{ $data['day_of_week'] ?? 'N/A' }}
    </div>
    <div class="text-sm font-mono text-green-500 dark:text-green-500 mt-2">
        {{ $data['year'] ?? 'N/A' }} AH
    </div>
</x-calendars.base-calendar-card>
