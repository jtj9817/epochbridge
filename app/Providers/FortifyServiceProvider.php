<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerTwoFactorChallengeView();
        $this->registerConfirmPasswordView();

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }

    protected function registerTwoFactorChallengeView(): void
    {
        Fortify::twoFactorChallengeView(function () {
            if (config('frontend.react_enabled')) {
                return Inertia::render('Auth/TwoFactorChallengePage', [
                    'recovery' => request()->session()->get('errors')?->has('recovery_code') ?? false,
                ]);
            }

            return view('livewire.auth.two-factor-challenge');
        });
    }

    protected function registerConfirmPasswordView(): void
    {
        Fortify::confirmPasswordView(function () {
            if (config('frontend.react_enabled')) {
                return Inertia::render('Auth/ConfirmPasswordPage');
            }

            return view('livewire.auth.confirm-password');
        });
    }
}
