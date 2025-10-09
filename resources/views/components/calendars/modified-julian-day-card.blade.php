@props(['data'])

<x-calendars.base-calendar-card
    title="Modified Julian Day"
    :copyText="$data"
    copyType="mjd"
    class="bg-orange-50/50 dark:bg-orange-950/30 border-orange-200 dark:border-orange-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </x-slot:icon>

    <p class="text-3xl font-mono text-orange-600 dark:text-orange-400 tracking-wider">
        {{ $data ?? 'N/A' }}
    </p>
    <p class="text-xs text-orange-500 dark:text-orange-500 mt-2 uppercase tracking-widest">Modified Julian Day</p>
</x-calendars.base-calendar-card>
