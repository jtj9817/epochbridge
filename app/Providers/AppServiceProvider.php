<?php

namespace App\Providers;

use App\Contracts\Calendar\FrenchRepublicanCalendarAdapter;
use App\Support\Calendars\FrenchRepublicanExtCalendarAdapter;
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
    }
}
