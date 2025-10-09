@props(['data'])

<x-calendars.base-calendar-card
    title="Babylonian"
    copyType="babylonian"
    :disclaimer="$data['disclaimer'] ?? null"
    class="bg-yellow-50/50 dark:bg-yellow-950/30 border-yellow-200 dark:border-yellow-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9V3m-3.5 3.5L12 3m0 0L8.5 6.5M12 21v-3" />
        </svg>
    </x-slot:icon>

    <div class="text-sm font-medium text-yellow-700 dark:text-yellow-300 tracking-wider">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-6xl font-bold text-yellow-600 dark:text-yellow-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-sm font-mono text-yellow-500 dark:text-yellow-500 mt-2">
        {{ $data['year'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
