@props(['data'])

<x-calendars.base-calendar-card
    title="French Republican"
    :copyText="$data['full_date'] ?? ''"
    copyType="french_republican"
    class="bg-blue-50/50 dark:bg-blue-950/30 border-blue-200 dark:border-blue-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
        </svg>
    </x-slot:icon>

    <div class="text-sm font-medium text-blue-700 dark:text-blue-300 tracking-wider">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-6xl font-bold text-blue-600 dark:text-blue-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-lg text-blue-600 dark:text-blue-300">
        {{ $data['day_of_week'] ?? 'N/A' }}
    </div>
    <div class="text-sm font-mono text-blue-500 dark:text-blue-500 mt-2">
        {{ $data['year'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
