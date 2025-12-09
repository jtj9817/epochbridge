@props(['data'])

<x-calendars.base-calendar-card
    title="Ancient Macedonian"
    copyType="macedonian"
    :disclaimer="$data['disclaimer'] ?? null"
    class="calendar-macedonian"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M12 14l9-5-9-5-9 5 9 5z" /> <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" /> <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222 4 2.222V20" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-cyan-700 dark:text-cyan-300 uppercase tracking-widest">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-5xl font-bold text-cyan-600 dark:text-cyan-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-xs font-mono text-cyan-500 dark:text-cyan-500 mt-2">
        {{ $data['year'] ?? 'N/A' }} SE
    </div>
</x-calendars.base-calendar-card>
