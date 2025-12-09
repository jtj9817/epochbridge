@props(['data'])

<x-calendars.base-calendar-card
    title="French Republican"
    :copyText="$data['full_date'] ?? ''"
    copyType="french_republican"
    class="calendar-french-republican"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-indigo-700 dark:text-indigo-300 uppercase tracking-widest">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-5xl font-bold text-indigo-600 dark:text-indigo-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-sm text-indigo-600 dark:text-indigo-300 uppercase tracking-wider">
        {{ $data['day_of_week'] ?? 'N/A' }}
    </div>
    <div class="text-xs font-mono text-indigo-500 dark:text-indigo-500 mt-2">
        AN {{ $data['year'] ?? 'N/A' }}
    </div>
    @if(!empty($data['display']))
        <div class="mt-3 text-xs text-indigo-700 dark:text-indigo-200 uppercase tracking-wider">
            {{ $data['display'] }}
        </div>
    @endif
</x-calendars.base-calendar-card>
