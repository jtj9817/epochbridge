<?php

namespace App\Services;

use App\Jobs\ExecuteTestSuiteJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TestExecutionService
{
    /**
     * Active test executions tracking
     */
    private array $activeExecutions = [];

    /**
     * Default execution options.
     *
     * @var array<string, mixed>
     */
    private array $defaultOptions = [
        'parallel' => true,
        'processes' => 4,
        'timeout' => 300,
        'retry_attempts' => 1,
        'report_file' => 'test-report.md',
    ];

    /**
     * Update the default options used for new executions.
     *
     * @param array<string, mixed> $options
     * @return void
     */
    public function setDefaultOptions(array $options): void
    {
        foreach ($options as $key => $value) {
            if ($value === null) {
                continue;
            }

            switch ($key) {
                case 'parallel':
                    $this->defaultOptions['parallel'] = (bool) $value;
                    break;
                case 'processes':
                    $this->defaultOptions['processes'] = (int) $value;
                    break;
                case 'timeout':
                    $this->defaultOptions['timeout'] = (int) $value;
                    break;
                case 'retry_attempts':
                    $this->defaultOptions['retry_attempts'] = (int) $value;
                    break;
                case 'report_file':
                    $this->defaultOptions['report_file'] = (string) $value;
                    break;
            }
        }
    }

    /**
     * Start test execution with the given parameters
     *
     * @param array $testFiles Selected test files
     * @param string $outputFile Output file path
     * @param array $options Execution options
     * @return string Command ID for tracking
     */
    public function startTestExecution(array $testFiles, string $outputFile, array $options = []): string
    {
        $options = array_merge($this->defaultOptions, $options);

        if ($outputFile === '' || $outputFile === null) {
            $outputFile = $options['report_file'] ?? $this->defaultOptions['report_file'];
        }

        $options['report_file'] = $outputFile;

        $commandId = $this->generateCommandId();
        
        Log::info("Starting test execution", [
            'command_id' => $commandId,
            'test_count' => count($testFiles),
            'output_file' => $outputFile,
            'options' => $options,
        ]);

        // Initialize tracking for this execution
        $totalPlanned = count($testFiles);
        if ($totalPlanned === 0 && isset($options['estimated_total_tests'])) {
            $totalPlanned = (int) $options['estimated_total_tests'];
        }

        $this->activeExecutions[$commandId] = [
            'id' => $commandId,
            'test_files' => $testFiles,
            'output_file' => $outputFile,
            'options' => $options,
            'status' => 'starting',
            'started_at' => now(),
            'completed_tests' => 0,
            'total_tests' => $totalPlanned,
            'progress' => 0.0,
        ];

        // Dispatch the test execution job
        try {
            ExecuteTestSuiteJob::dispatch($testFiles, $outputFile, $commandId, $options);
            
            $this->activeExecutions[$commandId]['status'] = 'running';
            
            Log::info("Test execution job dispatched", [
                'command_id' => $commandId,
            ]);
            
        } catch (\Exception $e) {
            $this->activeExecutions[$commandId]['status'] = 'failed';
            $this->activeExecutions[$commandId]['error'] = $e->getMessage();
            
            Log::error("Failed to dispatch test execution job", [
                'command_id' => $commandId,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }

        return $commandId;
    }

    /**
     * Track progress for a specific execution
     *
     * @param string $commandId Command identifier
     * @return array|null Progress information
     */
    public function trackProgress(string $commandId): ?array
    {
        if (!isset($this->activeExecutions[$commandId])) {
            return null;
        }

        $execution = $this->activeExecutions[$commandId];
        
        // Calculate progress percentage
        if ($execution['total_tests'] > 0) {
            $execution['progress'] = ($execution['completed_tests'] / $execution['total_tests']) * 100;
        }

        // Add estimated time remaining
        $execution['eta'] = $this->calculateETA($execution);

        return $execution;
    }

    /**
     * Handle completion of an individual test
     *
     * @param string $commandId Command identifier
     * @param string $testFile Test file path
     * @param string $status Test status (passed, failed, skipped)
     * @param float $duration Test duration
     * @param string $output Test output
     * @param string $errors Test errors
     * @return void
     */
    public function handleTestCompletion(
        string $commandId,
        string $testFile,
        string $status,
        float $duration,
        string $output = '',
        string $errors = ''
    ): void {
        if (!isset($this->activeExecutions[$commandId])) {
            Log::warning("Received test completion for unknown execution", [
                'command_id' => $commandId,
                'test_file' => $testFile,
            ]);
            return;
        }

        $this->activeExecutions[$commandId]['completed_tests']++;
        
        // Track individual test results
        if (!isset($this->activeExecutions[$commandId]['test_results'])) {
            $this->activeExecutions[$commandId]['test_results'] = [];
        }
        
        $this->activeExecutions[$commandId]['test_results'][] = [
            'file' => $testFile,
            'status' => $status,
            'duration' => $duration,
            'output' => $output,
            'errors' => $errors,
            'completed_at' => now(),
        ];

        Log::debug("Test completed", [
            'command_id' => $commandId,
            'test_file' => $testFile,
            'status' => $status,
            'duration' => $duration,
            'progress' => $this->activeExecutions[$commandId]['completed_tests'] . '/' . $this->activeExecutions[$commandId]['total_tests'],
        ]);
    }

    /**
     * Generate execution summary for completed test run
     *
     * @param string $commandId Command identifier
     * @param array $summary Execution summary data
     * @return array Complete execution report
     */
    public function generateExecutionSummary(string $commandId, array $summary): array
    {
        if (!isset($this->activeExecutions[$commandId])) {
            Log::warning("Received execution completion for unknown execution", [
                'command_id' => $commandId,
            ]);
            return $summary;
        }

        $execution = $this->activeExecutions[$commandId];
        
        // Mark as completed
        $this->activeExecutions[$commandId]['status'] = $summary['status'] ?? 'completed';
        $this->activeExecutions[$commandId]['completed_at'] = now();
        $this->activeExecutions[$commandId]['summary'] = $summary;

        // Calculate total execution time
        $totalTime = $execution['started_at']->diffInSeconds($this->activeExecutions[$commandId]['completed_at']);
        
        $completeReport = [
            'command_id' => $commandId,
            'execution_info' => [
                'started_at' => $execution['started_at'],
                'completed_at' => $this->activeExecutions[$commandId]['completed_at'],
                'total_execution_time' => $totalTime,
                'test_files_count' => $execution['total_tests'],
                'output_file' => $execution['output_file'],
                'options' => $execution['options'],
            ],
            'test_results' => $execution['test_results'] ?? [],
            'summary' => $summary,
            'status' => $summary['status'] ?? 'completed',
        ];

        Log::info("Test execution completed", [
            'command_id' => $commandId,
            'status' => $completeReport['status'],
            'total_time' => $totalTime,
            'tests_run' => $summary['total_tests'] ?? 0,
            'success_rate' => $summary['success_rate'] ?? 0,
        ]);

        return $completeReport;
    }

    /**
     * Get all active executions
     *
     * @return array Active executions
     */
    public function getActiveExecutions(): array
    {
        return $this->activeExecutions;
    }

    /**
     * Get execution status
     *
     * @param string $commandId Command identifier
     * @return string|null Execution status
     */
    public function getExecutionStatus(string $commandId): ?string
    {
        return $this->activeExecutions[$commandId]['status'] ?? null;
    }

    /**
     * Cancel an active execution
     *
     * @param string $commandId Command identifier
     * @return bool Success status
     */
    public function cancelExecution(string $commandId): bool
    {
        if (!isset($this->activeExecutions[$commandId])) {
            return false;
        }

        try {
            // Mark as cancelled
            $this->activeExecutions[$commandId]['status'] = 'cancelled';
            $this->activeExecutions[$commandId]['cancelled_at'] = now();

            Log::info("Test execution cancelled", [
                'command_id' => $commandId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to cancel test execution", [
                'command_id' => $commandId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean up completed executions older than specified time
     *
     * @param int $olderThanMinutes Minutes threshold for cleanup
     * @return int Number of executions cleaned up
     */
    public function cleanupCompletedExecutions(int $olderThanMinutes = 60): int
    {
        $cleanedUp = 0;
        $cutoffTime = now()->subMinutes($olderThanMinutes);

        foreach ($this->activeExecutions as $commandId => $execution) {
            if (
                in_array($execution['status'], ['completed', 'failed', 'cancelled']) &&
                isset($execution['completed_at']) &&
                $execution['completed_at']->lt($cutoffTime)
            ) {
                unset($this->activeExecutions[$commandId]);
                $cleanedUp++;
            }
        }

        if ($cleanedUp > 0) {
            Log::info("Cleaned up completed test executions", [
                'count' => $cleanedUp,
                'older_than_minutes' => $olderThanMinutes,
            ]);
        }

        return $cleanedUp;
    }

    /**
     * Calculate estimated time of arrival for execution completion
     *
     * @param array $execution Execution data
     * @return string|null ETA string or null if cannot calculate
     */
    private function calculateETA(array $execution): ?string
    {
        if (
            $execution['completed_tests'] === 0 ||
            $execution['total_tests'] === 0 ||
            !isset($execution['started_at'])
        ) {
            return null;
        }

        try {
            $elapsedSeconds = $execution['started_at']->diffInSeconds(now());
            $testsRemaining = $execution['total_tests'] - $execution['completed_tests'];
            $averageTimePerTest = $elapsedSeconds / $execution['completed_tests'];
            $estimatedSecondsRemaining = $testsRemaining * $averageTimePerTest;

            if ($estimatedSecondsRemaining < 60) {
                return round($estimatedSecondsRemaining) . ' seconds';
            } elseif ($estimatedSecondsRemaining < 3600) {
                return round($estimatedSecondsRemaining / 60) . ' minutes';
            } else {
                return round($estimatedSecondsRemaining / 3600, 1) . ' hours';
            }
        } catch (\Exception $e) {
            Log::warning("Failed to calculate ETA", [
                'command_id' => $execution['id'],
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Generate unique command identifier
     *
     * @return string Unique command ID
     */
    private function generateCommandId(): string
    {
        return 'test_' . Str::random(12) . '_' . time();
    }

    /**
     * Get execution report by command ID
     *
     * @param string $commandId Command identifier
     * @return array|null Execution report
     */
    public function getExecutionReport(string $commandId): ?array
    {
        if (!isset($this->activeExecutions[$commandId])) {
            return null;
        }

        $execution = $this->activeExecutions[$commandId];
        
        return [
            'command_id' => $commandId,
            'status' => $execution['status'],
            'started_at' => $execution['started_at'],
            'completed_at' => $execution['completed_at'] ?? null,
            'progress' => [
                'completed' => $execution['completed_tests'],
                'total' => $execution['total_tests'],
                'percentage' => $execution['total_tests'] > 0 
                    ? round(($execution['completed_tests'] / $execution['total_tests']) * 100, 2)
                    : 0,
            ],
            'eta' => $this->calculateETA($execution),
            'test_results' => $execution['test_results'] ?? [],
            'summary' => $execution['summary'] ?? null,
            'options' => $execution['options'],
            'output_file' => $execution['output_file'],
        ];
    }
}
