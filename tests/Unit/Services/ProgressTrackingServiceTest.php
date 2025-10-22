<?php

use App\Services\ProgressTrackingService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Mockery as m;
use Tests\TestCase;

uses(TestCase::class);

describe('ProgressTrackingService', function () {
    beforeEach(function () {
        $this->service = new ProgressTrackingService;
        $this->commandId = 'cmd-'.uniqid();
        $this->start = CarbonImmutable::create(2024, 10, 18, 12, 0, 0, 'UTC');
        Date::setTestNow($this->start);
    });

    afterEach(function () {
        Date::setTestNow();
        m::close();
    });

    it('initializes execution state with provided statistics', function () {
        $context = [
            'test_files' => ['tests/Unit/FooTest.php', 'tests/Feature/BarTest.php'],
            'options.processes' => 2,
        ];

        $this->service->initializeProgress(
            $this->commandId,
            5,
            ['unit' => 3, 'feature' => 2],
            $this->start,
            $context
        );

        $stats = $this->service->getStatistics($this->commandId);

        expect($stats['total'])->toBe(5)
            ->and($stats['by_type']['unit']['total'])->toBe(3)
            ->and($stats['by_type']['feature']['total'])->toBe(2)
            ->and($stats['summary'])->toBeNull();
    });

    it('tracks progress updates and aggregates by status and type', function () {
        $context = [
            'test_files' => [
                'tests/Unit/FooTest.php',
                'tests/Feature/BarTest.php',
                'tests/Browser/BazTest.php',
            ],
            'options.processes' => 2,
        ];

        $this->service->initializeProgress($this->commandId, 3, ['unit' => 1, 'feature' => 1, 'browser' => 1], $this->start, $context);

        $this->service->updateProgress($this->commandId, 'tests/Unit/FooTest.php', 'passed', 0.4);
        $this->service->updateProgress($this->commandId, 'tests/Feature/BarTest.php', 'failed', 1.25);
        $this->service->updateProgress($this->commandId, 'tests/Browser/BazTest.php', 'skipped', 0.1);

        $stats = $this->service->getStatistics($this->commandId);

        expect($stats['completed'])->toBe(3)
            ->and($stats['passed'])->toBe(1)
            ->and($stats['failed'])->toBe(1)
            ->and($stats['skipped'])->toBe(1)
            ->and($stats['average_duration'])->toBeFloat();
    });

    it('computes ETA based on remaining tests and average duration', function () {
        $this->service->initializeProgress($this->commandId, 5, [], $this->start);

        $eta = $this->service->calculateETA(2, 5, $this->start, 0.5);

        expect($eta)->toBe('1.5s');

        $complete = $this->service->calculateETA(5, 5, $this->start, 0.5);
        expect($complete)->toBe('—');
    });

    it('renders ascii progress bar proportionally', function () {
        $bar = $this->service->getProgressBar(3, 10, 20);

        expect($bar)->toStartWith('[█')
            ->and($bar)->toContain('30.0%');
    });

    it('updates running queue based on concurrency settings', function () {
        $context = [
            'test_files' => [
                'tests/Feature/AlphaTest.php',
                'tests/Feature/BetaTest.php',
                'tests/Feature/GammaTest.php',
            ],
            'options.processes' => 2,
        ];

        $this->service->initializeProgress($this->commandId, 3, [], $this->start, $context);

        $this->service->updateProgress($this->commandId, 'tests/Feature/AlphaTest.php', 'passed', 0.2);

        $reflection = new ReflectionClass($this->service);
        $executions = $reflection->getProperty('executions');
        $executions->setAccessible(true);
        $state = $executions->getValue($this->service);

        expect($state[$this->commandId]['currently_running'])->toHaveCount(2)
            ->and($state[$this->commandId]['pending'])->toHaveCount(2);
    });

    it('resets executions when cleared', function () {
        $this->service->initializeProgress($this->commandId, 1, [], $this->start);
        $this->service->clear();

        $stats = $this->service->getStatistics($this->commandId);

        expect($stats)->toBe([]);
    });

    it('finalizes execution summary for downstream consumers', function () {
        $summary = ['status' => 'passed', 'total_tests' => 5];

        $this->service->initializeProgress($this->commandId, 5, [], $this->start);
        $this->service->finalizeSummary($this->commandId, $summary);

        $stats = $this->service->getStatistics($this->commandId);

        expect($stats['summary'])->toBe($summary);
    });
});
