@props(['data'])

<x-calendars.base-calendar-card
    title="ISO 8601"
    :copyText="$data"
    copyType="iso8601"
    class="bg-zinc-50 dark:bg-black/20 border-zinc-200 dark:border-zinc-700"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2h8a2 2 0 002-2v-1a2 2 0 012-2h1.945M7.707 4.293l.707-.707a2 2 0 012.828 0l.707.707M7.707 4.293l.707.707a2 2 0 002.828 0l.707-.707M15 21v-3.5a2.5 2.5 0 00-5 0V21M3 11a9 9 0 1118 0 9 9 0 01-18 0z" />
        </svg>
    </x-slot:icon>

    <p class="text-xl font-mono text-zinc-800 dark:text-zinc-100 break-all">
        {{ $data ?? 'N/A' }}
    </p>
    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 uppercase tracking-widest">UTC format</p>
</x-calendars.base-calendar-card>
