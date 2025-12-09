<div class="flex items-start max-md:flex-col">
    <div class="me-10 w-full pb-4 md:w-[220px] border-r-2 border-theme-300 dark:border-zinc-700 pr-6">
        <flux:navlist>
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('password.edit')" wire:navigate>{{ __('Password') }}</flux:navlist.item>
            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <flux:navlist.item :href="route('two-factor.show')" wire:navigate>{{ __('Two-Factor Auth') }}</flux:navlist.item>
            @endif
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
    </div>

    <div class="border-t-2 border-theme-300 dark:border-zinc-700 w-full pt-4 md:hidden"></div>

    <div class="flex-1 self-stretch max-md:pt-4 md:pl-6">
        <h2 class="text-sm font-bold text-theme-900 dark:text-theme-50 uppercase tracking-widest">{{ $heading ?? '' }}</h2>
        <p class="text-xs text-theme-500 dark:text-zinc-400 uppercase tracking-wider mt-1">{{ $subheading ?? '' }}</p>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
