<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
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
        $this->registerUserActions();

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }

    /**
     * Register the customizable Fortify user management actions.
     */
    protected function registerUserActions(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
    }

    protected function registerTwoFactorChallengeView(): void
    {
        Fortify::twoFactorChallengeView(function () {
            return Inertia::render('Auth/TwoFactorChallengePage', [
                'recovery' => request()->session()->get('errors')?->has('recovery_code') ?? false,
            ]);
        });
    }

    protected function registerConfirmPasswordView(): void
    {
        Fortify::confirmPasswordView(function () {
            return Inertia::render('Auth/ConfirmPasswordPage');
        });
    }
}
