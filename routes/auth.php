<?php

use App\Http\Controllers\Auth\DeleteAccountController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\React\Auth\ForgotPasswordPageController;
use App\Http\Controllers\React\Auth\LoginPageController;
use App\Http\Controllers\React\Auth\RegisterPageController;
use App\Http\Controllers\React\Auth\ResetPasswordPageController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\PasswordResetLinkController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController;

Route::middleware('guest')->group(function () {
    Route::get('login', LoginPageController::class)->name('login');
    Route::get('register', RegisterPageController::class)->name('register');
    Route::get('forgot-password', ForgotPasswordPageController::class)->name('password.request');
    Route::get('reset-password/{token}', ResetPasswordPageController::class)->name('password.reset');

    // Same Fortify handlers, re-declared with throttling; these replace the
    // package routes because route files load after package providers.
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:auth-register')
        ->name('register.store');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:auth-forgot-password')
        ->name('password.email');

    Route::prefix('auth')->group(function () {
        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])
            ->name('social.redirect')
            ->where('provider', 'google');

        Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
            ->name('social.callback')
            ->where('provider', 'google');
    });
});

Route::middleware('auth')->group(function () {
    Route::delete('user', DeleteAccountController::class)
        ->name('current-user.destroy');
});

Route::post('logout', LogoutController::class)
    ->name('logout');
