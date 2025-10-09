@props(['data'])

<x-calendars.base-calendar-card
    title="Coptic"
    :copyText="$data['full_date'] ?? ''"
    copyType="coptic"
    class="bg-red-50/50 dark:bg-red-950/30 border-red-200 dark:border-red-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
    </x-slot:icon>

    <div class="text-sm font-medium text-red-700 dark:text-red-300 tracking-wider">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-6xl font-bold text-red-600 dark:text-red-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-lg text-red-600 dark:text-red-300">
        {{ $data['day_of_week'] ?? 'N/A' }}
    </div>
    <div class="text-sm font-mono text-red-500 dark:text-red-500 mt-2">
        {{ $data['year'] ?? 'N/A' }} AM
    </div>
</x-calendars.base-calendar-card>
