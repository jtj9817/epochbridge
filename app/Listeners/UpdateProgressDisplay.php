<?php

namespace App\Listeners;

use App\Events\TestCompleted;
use App\Events\TestExecutionCompleted;
use App\Events\TestExecutionStarted;
use App\Services\ProgressTrackingService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class UpdateProgressDisplay
{
    private ProgressTrackingService $progressTrackingService;

    public function __construct(ProgressTrackingService $progressTrackingService)
    {
        $this->progressTrackingService = $progressTrackingService;
    }

    /**
     * Handle the TestExecutionStarted event.
     *
     * @param \App\Events\TestExecutionStarted $event
     * @return void
     */
    public function handleTestExecutionStarted(TestExecutionStarted $event): void
    {
        $totalTests = Arr::get($event->metadata, 'estimated_total_tests', $event->testCount);
        $testTypes = Arr::get($event->metadata, 'tests_by_type', []);

        $context = [
            'options' => Arr::get($event->metadata, 'options', []),
            'estimated_total_tests' => $totalTests,
            'test_files' => Arr::get($event->metadata, 'test_files', []),
        ];

        $this->progressTrackingService->setActiveCommand($event->commandId);
        $this->progressTrackingService->initializeProgress(
            $event->commandId,
            (int) $totalTests,
            $testTypes,
            $event->startTime,
            $context
        );

        $this->progressTrackingService->displayCurrentStatus(null, $event->commandId, true);
    }

    /**
     * Handle the TestCompleted event.
     *
     * @param \App\Events\TestCompleted $event
     * @return void
     */
    public function handleTestCompleted(TestCompleted $event): void
    {
        $commandId = Arr::get($event->metadata, 'command_id');

        if (!$commandId) {
            Log::debug('Progress update ignored due to missing command identifier', [
                'test_file' => $event->testFile,
            ]);
            return;
        }

        $this->progressTrackingService->setActiveCommand($commandId);
        $this->progressTrackingService->updateProgress(
            $commandId,
            $event->testFile,
            $event->status,
            $event->duration
        );

        $this->refreshDisplay($commandId);
    }

    /**
     * Handle the TestExecutionCompleted event.
     *
     * @param \App\Events\TestExecutionCompleted $event
     * @return void
     */
    public function handleTestExecutionCompleted(TestExecutionCompleted $event): void
    {
        $this->progressTrackingService->setActiveCommand($event->commandId);
        $this->progressTrackingService->finalizeSummary($event->commandId, $event->summary);
        $this->refreshDisplay($event->commandId, true);
    }

    /**
     * Refresh progress display if output is registered.
     *
     * @param string $commandId
     * @param bool $force
     * @return void
     */
    private function refreshDisplay(string $commandId, bool $force = false): void
    {
        if (!app()->bound('interactive-test-runner.output')) {
            return;
        }

        $output = app('interactive-test-runner.output');
        $this->progressTrackingService->registerOutput($output);
        $this->progressTrackingService->displayCurrentStatus($output, $commandId, $force);
    }
}

