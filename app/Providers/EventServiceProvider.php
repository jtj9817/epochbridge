<?php

namespace App\Providers;

use App\Events\TestCompleted;
use App\Events\TestExecutionCompleted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string|string>>
     */
    protected $listen = [
        TestCompleted::class => [
            \App\Listeners\UpdateMarkdownReport::class . '@handleTestCompleted',
        ],
        TestExecutionCompleted::class => [
            \App\Listeners\UpdateMarkdownReport::class . '@handleTestExecutionCompleted',
        ],
    ];
}

