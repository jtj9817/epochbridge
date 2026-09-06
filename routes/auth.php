<?php

use App\Http\Controllers\Auth\DeleteAccountController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\React\Auth\ForgotPasswordPageController;
use App\Http\Controllers\React\Auth\LoginPageController;
use App\Http\Controllers\React\Auth\RegisterPageController;
use App\Http\Controllers\React\Auth\ResetPasswordPageController;
use App\Http\Controllers\React\Auth\VerifyEmailPageController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', LoginPageController::class)->name('login');
    Route::get('register', RegisterPageController::class)->name('register');
    Route::get('forgot-password', ForgotPasswordPageController::class)->name('password.request');
    Route::get('reset-password/{token}', ResetPasswordPageController::class)->name('password.reset');

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
    Route::get('verify-email', VerifyEmailPageController::class)->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::delete('user', DeleteAccountController::class)
        ->name('current-user.destroy');
});

Route::post('logout', LogoutController::class)
    ->name('logout');
