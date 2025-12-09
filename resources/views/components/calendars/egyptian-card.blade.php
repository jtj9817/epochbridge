@props(['data'])

<x-calendars.base-calendar-card
    title="Egyptian"
    :copyText="$data['full_date'] ?? ''"
    copyType="egyptian"
    :disclaimer="$data['disclaimer'] ?? null"
    class="calendar-egyptian"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
        </svg>
    </x-slot:icon>

    <div class="text-xs font-medium text-amber-700 dark:text-amber-300 uppercase tracking-widest">
        {{ $data['month_name'] ?? 'N/A' }}
    </div>
    <div class="text-5xl font-bold text-amber-600 dark:text-amber-400 my-1">
        {{ $data['day'] ?? 'N/A' }}
    </div>
    <div class="text-xs font-mono text-amber-500 dark:text-amber-500 mt-2">
        {{ $data['year'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
