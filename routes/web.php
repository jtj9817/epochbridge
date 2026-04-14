<?php

use App\Http\Controllers\React\Dashboard\DashboardPageController;
use App\Http\Controllers\React\LandingController;
use App\Http\Controllers\React\Settings\AppearancePageController;
use App\Http\Controllers\React\Settings\PasswordPageController;
use App\Http\Controllers\React\Settings\ProfilePageController;
use App\Http\Controllers\React\Settings\TwoFactorPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Livewire\Volt\Volt;

$reactEnabled = config('frontend.react_enabled');

// React/Inertia routes (only when feature flag enabled)
if ($reactEnabled) {
    Route::get('/react-test', fn () => Inertia::render('Test'))->name('react.test');
    Route::get('/react', LandingController::class)->name('react.landing');
}

Volt::route('/', 'landing')->name('home');

if ($reactEnabled) {
    Route::get('dashboard', DashboardPageController::class)
        ->middleware(['auth', 'verified'])
        ->name('dashboard');
} else {
    Route::view('dashboard', 'dashboard')
        ->middleware(['auth', 'verified'])
        ->name('dashboard');
}

Route::middleware(['auth'])->group(function () use ($reactEnabled) {
    Route::redirect('settings', 'settings/profile');

    if ($reactEnabled) {
        Route::get('settings/profile', ProfilePageController::class)->name('profile.edit');
        Route::get('settings/password', PasswordPageController::class)->name('password.edit');
        Route::get('settings/appearance', AppearancePageController::class)->name('appearance.edit');

        Route::get('settings/two-factor', TwoFactorPageController::class)
            ->middleware(
                when(
                    Features::canManageTwoFactorAuthentication()
                        && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                    ['password.confirm'],
                    [],
                ),
            )
            ->name('two-factor.show');
    } else {
        Volt::route('settings/profile', 'settings.profile')->name('profile.edit');
        Volt::route('settings/password', 'settings.password')->name('password.edit');
        Volt::route('settings/appearance', 'settings.appearance')->name('appearance.edit');

        Volt::route('settings/two-factor', 'settings.two-factor')
            ->middleware(
                when(
                    Features::canManageTwoFactorAuthentication()
                        && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                    ['password.confirm'],
                    [],
                ),
            )
            ->name('two-factor.show');
    }
});

require __DIR__.'/auth.php';
