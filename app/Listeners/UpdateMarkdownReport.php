<?php

namespace App\Listeners;

use App\Events\TestCompleted;
use App\Events\TestExecutionCompleted;
use App\Services\MarkdownReportService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class UpdateMarkdownReport
{
    /**
     * Create a new listener instance.
     *
     * @param \App\Services\MarkdownReportService $markdownReportService
     */
    public function __construct(private readonly MarkdownReportService $markdownReportService)
    {
    }

    /**
     * Handle the TestCompleted event.
     *
     * @param \App\Events\TestCompleted $event
     * @return void
     */
    public function handleTestCompleted(TestCompleted $event): void
    {
        $outputFile = Arr::get($event->metadata, 'output_file', 'test-report.md');

        try {
            $this->markdownReportService->updateTestResult(
                $outputFile,
                $event->testFile,
                $event->status,
                $event->duration,
                $event->output,
                $event->errors
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to update markdown report for test result', [
                'test_file' => $event->testFile,
                'output_file' => $outputFile,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Handle the TestExecutionCompleted event.
     *
     * @param \App\Events\TestExecutionCompleted $event
     * @return void
     */
    public function handleTestExecutionCompleted(TestExecutionCompleted $event): void
    {
        $outputFile = Arr::get($event->metadata, 'output_file', 'test-report.md');

        $summary = $event->summary;
        $summary['command'] = Arr::get(
            $event->metadata,
            'command',
            $summary['command'] ?? 'php artisan test --parallel'
        );
        $summary['processes'] = Arr::get($event->metadata, 'options.processes', $summary['processes'] ?? null);
        $summary['executed_at'] = $event->endTime->toDateTimeString();

        try {
            $this->markdownReportService->generateSummarySection($outputFile, $summary);
        } catch (\Throwable $exception) {
            Log::error('Failed to finalize markdown report summary', [
                'output_file' => $outputFile,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}

