<?php

namespace App\Providers;

use App\Events\TestCompleted;
use App\Events\TestExecutionCompleted;
use App\Events\TestExecutionStarted;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string|string>>
     */
    protected $listen = [
        TestExecutionStarted::class => [
            \App\Listeners\UpdateProgressDisplay::class . '@handleTestExecutionStarted',
        ],
        TestCompleted::class => [
            \App\Listeners\UpdateMarkdownReport::class . '@handleTestCompleted',
            \App\Listeners\UpdateProgressDisplay::class . '@handleTestCompleted',
        ],
        TestExecutionCompleted::class => [
            \App\Listeners\UpdateMarkdownReport::class . '@handleTestExecutionCompleted',
            \App\Listeners\UpdateProgressDisplay::class . '@handleTestExecutionCompleted',
        ],
    ];
}
