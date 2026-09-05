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

// Landing page
Route::get('/', LandingController::class)->name('home');
Route::get('/react-test', fn () => Inertia::render('Test'))->name('react.test');

Route::get('dashboard', DashboardPageController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

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
});

require __DIR__.'/auth.php';
