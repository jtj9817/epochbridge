@props(['data'])

<x-calendars.base-calendar-card
    title="Coptic"
    :copyText="$data['full_date'] ?? ''"
    copyType="coptic"
    class="calendar-coptic"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-orange-700 dark:text-orange-300 uppercase tracking-widest">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-5xl font-bold text-orange-600 dark:text-orange-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-sm text-orange-600 dark:text-orange-300 uppercase tracking-wider">
        {{ $data['day_of_week'] ?? 'N/A' }}
    </div>
    <div class="text-xs font-mono text-orange-500 dark:text-orange-500 mt-2">
        {{ $data['year'] ?? 'N/A' }} AM
    </div>
</x-calendars.base-calendar-card>
