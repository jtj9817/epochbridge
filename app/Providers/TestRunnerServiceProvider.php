<?php

namespace App\Providers;

use App\Services\ErrorRecoveryService;
use App\Services\MarkdownReportService;
use App\Services\ProgressTrackingService;
use App\Services\TestDiscoveryService;
use App\Services\TestExecutionService;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;

class TestRunnerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('test-runner.php'), 'test-runner');

        $this->app->singleton(ProgressTrackingService::class, function () {
            return new ProgressTrackingService();
        });

        $this->app->singleton(MarkdownReportService::class, function () {
            return new MarkdownReportService();
        });

        $this->app->singleton(ErrorRecoveryService::class, function () {
            return new ErrorRecoveryService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureTestExecutionDefaults();
    }

    /**
     * Apply configuration defaults to test execution service.
     *
     * @return void
     */
    protected function configureTestExecutionDefaults(): void
    {
        $config = config('test-runner', []);
        $env = config('app.env');
        $overrides = Arr::get($config, "environments.{$env}", []);
        $settings = array_merge($config, $overrides);

        $this->app->resolving(TestExecutionService::class, function (TestExecutionService $service) use ($settings) {
            $service->setDefaultOptions([
                'parallel' => $settings['parallel'] ?? true,
                'processes' => $settings['processes'] ?? 4,
                'timeout' => $settings['timeout'] ?? 300,
                'retry_attempts' => $settings['retry_attempts'] ?? 1,
                'report_file' => Arr::get($settings, 'report.output_file'),
            ]);
        });

        $this->app->resolving(TestDiscoveryService::class, function (TestDiscoveryService $service) use ($settings) {
            $service->setDiscoveryConfiguration([
                'paths' => $settings['paths'] ?? [],
                'patterns' => $settings['patterns'] ?? [],
            ]);
        });
    }
}
