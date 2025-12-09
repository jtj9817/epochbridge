@props(['data'])

<x-calendars.base-calendar-card
    title="Mayan"
    copyType="mayan"
    :disclaimer="$data['disclaimer'] ?? null"
    class="calendar-mayan md:col-span-2 lg:col-span-1"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.196-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.783-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
    </x-slot:icon>

    <div class="space-y-3 w-full">
        <div>
            <p class="text-xs text-stone-500 dark:text-stone-400 uppercase tracking-widest">Long Count</p>
            <p class="text-xl font-mono text-stone-700 dark:text-stone-300">{{ $data['long_count'] ?? 'N/A' }}</p>
        </div>
        <div class="flex justify-between gap-4">
            <div class="flex-1">
                <p class="text-xs text-stone-500 dark:text-stone-400 uppercase tracking-widest">Tzolk'in</p>
                <p class="text-base font-semibold text-stone-600 dark:text-stone-200">{{ $data['tzolkin'] ?? 'N/A' }}</p>
            </div>
            <div class="flex-1">
                <p class="text-xs text-stone-500 dark:text-stone-400 uppercase tracking-widest">Haab'</p>
                <p class="text-base font-semibold text-stone-600 dark:text-stone-200">{{ $data['haab'] ?? 'N/A' }}</p>
            </div>
        </div>
    </div>
</x-calendars.base-calendar-card>
