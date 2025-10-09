@props([
    'title',
    'copyText' => null,
    'copyType',
    'disclaimer' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white dark:bg-zinc-800/50 rounded-xl shadow-lg border border-zinc-200 dark:border-zinc-700 flex flex-col']) }}>
    <div class="p-4 border-b border-zinc-200/80 dark:border-zinc-700/80 flex items-center justify-between">
        <h3 class="flex items-center gap-2.5 font-bold text-zinc-700 dark:text-zinc-200">
            @if(isset($icon))
                {{ $icon }}
            @endif
            {{ $title }}
        </h3>
        <div class="flex items-center gap-2">
            @if($disclaimer)
                <div x-data="{ show: false }" class="relative">
                    <button @mouseenter="show = true" @mouseleave="show = false" class="text-zinc-500 dark:text-zinc-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </button>
                    <div x-show="show" x-transition class="absolute bottom-full right-0 mb-2 w-64 p-2 text-xs bg-zinc-800 text-white rounded-md z-10">
                        {{ $disclaimer }}
                    </div>
                </div>
            @endif
            @if($copyText)
                <button @click="copyToClipboard('{{ $copyText }}', '{{ $copyType }}')" title="Copy" class="text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-100 transition-colors">
                    <span x-show="copiedFeedback !== '{{ $copyType }}'">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </span>
                    <span x-show="copiedFeedback === '{{ $copyType }}'" x-cloak class="text-green-500 text-xs">Copied!</span>
                </button>
            @endif
        </div>
    </div>
    <div class="flex-grow flex flex-col items-center justify-center p-6 text-center">
        {{ $slot }}
    </div>
</div>
