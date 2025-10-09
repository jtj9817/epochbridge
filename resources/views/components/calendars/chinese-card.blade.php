@props(['data'])

<x-calendars.base-calendar-card
    title="Chinese"
    :copyText="$data['full_date'] ?? ''"
    copyType="chinese"
    class="bg-red-50/50 dark:bg-red-950/30 border-red-200 dark:border-red-800"
>
    <x-slot:icon>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
             <path stroke-linecap="round" stroke-linejoin="round" d="M15.5,1.5c-0.9-1.2-2.3-1.9-3.9-1.9c-1.6,0-3,0.7-3.9,1.9C5.3,4.4,4,7.8,4,11.3c0,2.3,0.6,4.5,1.7,6.4c1.1,1.9,2.7,3.5,4.6,4.6c1.9,1.1,4.1,1.7,6.4,1.7c3.5,0,6.9-1.3,9.8-3.7c-2.9-2.4-6.3-3.7-9.8-3.7c-3.2,0-6.2,1-8.8,2.8" />
        </svg>
    </x-slot:icon>

    <div class="text-sm font-medium text-red-700 dark:text-red-300 tracking-wider">
        {{ $data['month_name'] ?? 'N/A' }}
        @if($data['is_leap_month']) (Leap) @endif
    </div>
    <div class="text-6xl font-bold text-red-600 dark:text-red-400 my-1">
        {{ $data['day_name'] ?? 'N/A' }}
    </div>
    <div class="text-lg text-red-600 dark:text-red-300">
        {{ $data['zodiac_animal'] ?? '' }}
    </div>
    <div class="text-sm font-mono text-red-500 dark:text-red-500 mt-2">
        {{ $data['year'] ?? 'N/A' }}
    </div>
</x-calendars.base-calendar-card>
