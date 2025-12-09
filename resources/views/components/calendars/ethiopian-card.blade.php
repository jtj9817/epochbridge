@props(['data'])

<x-calendars.base-calendar-card
    title="Ethiopian"
    :copyText="$data['full_date'] ?? ''"
    copyType="ethiopian"
    class="calendar-ethiopian"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-emerald-700 dark:text-emerald-300 uppercase tracking-widest">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-5xl font-bold text-emerald-600 dark:text-emerald-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-sm text-emerald-600 dark:text-emerald-300 uppercase tracking-wider">
        {{ $data['day_of_week'] ?? 'N/A' }}
    </div>
    <div class="text-xs font-mono text-emerald-500 dark:text-emerald-500 mt-2">
        {{ $data['year'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
