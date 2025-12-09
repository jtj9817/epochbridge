@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center border-b border-theme-300 dark:border-zinc-700 pb-4 mb-2">
    <h1 class="text-lg font-bold text-theme-900 dark:text-theme-50 uppercase tracking-wider">{{ $title }}</h1>
    <p class="text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider mt-1">{{ $description }}</p>
</div>
