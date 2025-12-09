@props(['data'])

<x-calendars.base-calendar-card
    title="Gregorian"
    :copyText="$data['full_date'] ?? ''"
    copyType="gregorian"
    class="calendar-gregorian"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-blue-700 dark:text-blue-300 uppercase tracking-widest">
        {{ $data['month_name'] }}
    </div>
    <div class="text-5xl font-bold text-blue-600 dark:text-blue-400 my-1">
        {{ $data['day'] }}
    </div>
    <div class="text-sm text-blue-600 dark:text-blue-300 uppercase tracking-wider">
        {{ $data['day_of_week'] }}
    </div>
    <div class="text-xs font-mono text-blue-500 dark:text-blue-500 mt-2">
        {{ $data['year'] }}
    </div>
</x-calendars.base-calendar-card>
