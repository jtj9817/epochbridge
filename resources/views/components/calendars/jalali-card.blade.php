@props(['data'])

<x-calendars.base-calendar-card
    title="Jalali"
    :copyText="$data['full_date'] ?? ''"
    copyType="jalali"
    class="calendar-jalali"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-teal-700 dark:text-teal-300 tracking-wider">
        {{ $data['month_name'] }}
        <span class="block text-xs opacity-75 uppercase tracking-widest">{{ $data['month_name_en'] }}</span>
    </div>
    <div class="text-5xl font-bold text-teal-600 dark:text-teal-400 my-1">
        {{ $data['day'] }}
    </div>
    <div class="text-sm text-teal-600 dark:text-teal-300 uppercase tracking-wider">
        {{ $data['day_of_week'] ?? '' }}
        <span class="block text-xs opacity-75">{{ $data['day_of_week_en'] ?? '' }}</span>
    </div>
    <div class="text-xs font-mono text-teal-500 dark:text-teal-500 mt-2">
        {{ $data['year'] }}
    </div>
</x-calendars.base-calendar-card>
