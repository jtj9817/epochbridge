@props(['data'])

<x-calendars.base-calendar-card
    title="Japanese"
    :copyText="$data['full_date'] ?? ''"
    copyType="japanese"
    class="calendar-japanese"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
             <circle cx="12" cy="12" r="10" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-rose-700 dark:text-rose-300 uppercase tracking-widest">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-5xl font-bold text-rose-600 dark:text-rose-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-sm text-rose-600 dark:text-rose-300 uppercase tracking-wider">
        {{ $data['era_name_japanese'] ?? '' }}
        <span class="block text-xs opacity-75">{{ $data['era_name_romanized'] ?? '' }}</span>
    </div>
    <div class="text-xs font-mono text-rose-500 dark:text-rose-500 mt-2">
        YR {{ $data['year_in_era'] ?? 'N/A' }} · {{ $data['era_name_english'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
