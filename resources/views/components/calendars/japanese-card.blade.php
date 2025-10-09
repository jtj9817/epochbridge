@props(['data'])

<x-calendars.base-calendar-card
    title="Japanese"
    :copyText="$data['full_date'] ?? ''"
    copyType="japanese"
    class="bg-rose-50/50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
             <circle cx="12" cy="12" r="10" />
        </svg>
    </x-slot:icon>

    <div class="text-sm font-medium text-rose-700 dark:text-rose-300 tracking-wider">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-6xl font-bold text-rose-600 dark:text-rose-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-lg text-rose-600 dark:text-rose-300">
        {{ $data['era_name_japanese'] ?? '' }}
        <span class="block text-sm italic opacity-75">{{ $data['era_name_romanized'] ?? '' }}</span>
    </div>
    <div class="text-sm font-mono text-rose-500 dark:text-rose-500 mt-2">
        Year {{ $data['year_in_era'] ?? 'N/A' }} of {{ $data['era_name_english'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
