@props(['data'])

<x-calendars.base-calendar-card
    title="Unix"
    :copyText="$data"
    copyType="unix"
    class="calendar-unix"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
    </x-slot:icon>

    <p class="text-2xl font-mono text-green-600 dark:text-green-400 tracking-wider">
        {{ $data ?? 'N/A' }}
    </p>
    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 uppercase tracking-widest">seconds since epoch</p>
</x-calendars.base-calendar-card>
