@props(['data'])

<x-calendars.base-calendar-card
    title="Julian Day"
    :copyText="$data"
    copyType="jd"
    class="bg-purple-50/50 dark:bg-purple-950/30 border-purple-200 dark:border-purple-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </x-slot:icon>

    <p class="text-3xl font-mono text-purple-600 dark:text-purple-400 tracking-wider">
        {{ $data ?? 'N/A' }}
    </p>
    <p class="text-xs text-purple-500 dark:text-purple-500 mt-2 uppercase tracking-widest">Julian Day Number</p>
</x-calendars.base-calendar-card>
