@props(['data'])

@php
    $monthName = $data['month_name'] ?? 'N/A';
    $day = $data['day'] ?? 'N/A';
    $dayOfWeek = $data['day_of_week'] ?? 'N/A';
    $year = $data['year'] ?? 'N/A';
    $fullDate = $data['full_date'] ?? '';
    $difference = $data['difference_label'] ?? null;
@endphp

<x-calendars.base-calendar-card
    title="Julian Calendar"
    :copyText="$fullDate"
    copyType="julian-calendar"
    class="bg-amber-50/50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-700"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
    </x-slot:icon>

    <div class="text-sm font-medium text-amber-700 dark:text-amber-200 uppercase tracking-wider">
        {{ $monthName }}
    </div>
    <div class="text-6xl font-bold text-amber-600 dark:text-amber-300 my-1">
        {{ $day }}
    </div>
    <div class="text-lg text-amber-700 dark:text-amber-200">
        {{ $dayOfWeek }}
    </div>
    <div class="text-sm font-mono text-amber-500 dark:text-amber-400 mt-2">
        {{ $year }}
    </div>

    <div class="mt-4 space-y-2 text-sm text-amber-800 dark:text-amber-200">
        @if($fullDate)
            <p><span class="font-semibold">Full date:</span> {{ $fullDate }}</p>
        @endif
        @if($difference)
            <p><span class="font-semibold">Difference:</span> {{ $difference }}</p>
        @endif
    </div>
</x-calendars.base-calendar-card>

