@props(['data'])

<x-calendars.base-calendar-card
    title="Julian Calendar"
    :copyText="$data['full_date'] ?? ''"
    copyType="julian-calendar"
    class="calendar-julian"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-amber-700 dark:text-amber-300 uppercase tracking-widest">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-5xl font-bold text-amber-600 dark:text-amber-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-sm text-amber-600 dark:text-amber-300 uppercase tracking-wider">
        {{ $data['day_of_week'] ?? 'N/A' }}
    </div>
    <div class="text-xs font-mono text-amber-500 dark:text-amber-500 mt-2">
        {{ $data['year'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>

