<?php

namespace App\Providers;

use App\Contracts\Calendar\FrenchRepublicanCalendarAdapter;
use App\Support\Calendars\FrenchRepublicanExtCalendarAdapter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FrenchRepublicanCalendarAdapter::class, FrenchRepublicanExtCalendarAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perDay(5)->by(($request->input('email') ?? '').'|'.$request->ip()),
            ];
        });
    }
}
