<?php

namespace App\Jobs;

use App\Events\TestCompleted;
use App\Events\TestExecutionCompleted;
use App\Events\TestExecutionStarted;
use App\Exceptions\TestExecutionException;
use App\Services\ErrorRecoveryService;
use App\Services\MarkdownReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ExecuteTestSuiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The test files to execute
     */
    public array $testFiles;

    /**
     * The output file for the report
     */
    public string $outputFile;

    /**
     * Unique command identifier
     */
    public string $commandId;

    /**
     * Additional execution options
     */
    public array $options;

    /**
     * Base command string used for execution
     */
    private string $baseCommand;

    /**
     * The number of times the job may be attempted
     */
    public int $tries = 3;

    /**
     * The maximum number of unhandled exceptions to allow before failing
     */
    public int $maxExceptions = 3;

    /**
     * Create a new job instance
     *
     * @param array $testFiles Selected test files
     * @param string $outputFile Output file path
     * @param string $commandId Unique identifier
     * @param array $options Additional options
     */
    public function __construct(array $testFiles, string $outputFile, string $commandId, array $options = [])
    {
        $this->testFiles = $testFiles;
        $this->outputFile = $outputFile;
        $this->commandId = $commandId;
        $this->options = array_merge([
            'parallel' => true,
            'processes' => 4,
            'stop_on_failure' => false,
            'coverage' => false,
            'timeout' => 300, // 5 minutes default timeout
            'retry_attempts' => 1,
        ], $options);

        $this->baseCommand = $this->buildTestCommand();
    }

    /**
     * Determine the delay between queued retries.
     *
     * @return array<int>
     */
    public function backoff(): array
    {
        return [5, 15, 30, 60];
    }

    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(): void
    {
        /** @var ErrorRecoveryService $errorRecoveryService */
        $errorRecoveryService = app(ErrorRecoveryService::class);

        try {
            $runStartedAt = now();

            // Dispatch execution started event
            TestExecutionStarted::dispatch(
                $this->commandId,
                count($this->testFiles),
                $runStartedAt,
                $this->buildStartMetadata()
            );

            Log::info("Starting test execution", [
                'command_id' => $this->commandId,
                'test_count' => count($this->testFiles),
                'options' => $this->options,
            ]);

            app(MarkdownReportService::class)->initializeReport(
                $this->outputFile,
                $this->estimateTotalTests(),
                $runStartedAt
            );

            $results = [];
            $startTime = microtime(true);

            if (empty($this->testFiles)) {
                // Execute all tests with recovery awareness
                $suiteResult = $this->runTestWithRecovery(null, $errorRecoveryService);
                $suiteResult['file'] = 'Full Test Suite';
                $results[] = $suiteResult;
            } else {
                // Execute specific test files
                foreach ($this->testFiles as $testFile) {
                    $result = $this->runTestWithRecovery($testFile, $errorRecoveryService);
                    $result['file'] = is_array($testFile)
                        ? ($testFile['file_path'] ?? 'unknown')
                        : $testFile;
                    $results[] = $result;

                    // Dispatch individual test completion event
                    TestCompleted::dispatch(
                        $testFile['file_path'] ?? $testFile,
                        $result['status'],
                        $result['duration'],
                        $result['output'],
                        $result['errors'],
                        [
                            'output_file' => $this->outputFile,
                            'command_id' => $this->commandId,
                            'options' => $this->options,
                            'command' => $this->baseCommand,
                            'started_at' => $runStartedAt->toISOString(),
                            'tests_by_type' => $this->options['tests_by_type'] ?? [],
                            'estimated_total_tests' => $this->options['estimated_total_tests'] ?? count($this->testFiles),
                            'test_files' => $this->normalizeTestFiles($this->testFiles),
                            'attempt' => $result['attempts'] ?? 1,
                            'recovery' => $result['recovery'] ?? null,
                        ]
                    );
                }
            }

            $endTime = microtime(true);
            $totalDuration = round($endTime - $startTime, 2);

            // Generate execution summary
            $summary = $this->generateExecutionSummary($results, $totalDuration);

            Log::info("Test execution completed", [
                'command_id' => $this->commandId,
                'duration' => $totalDuration,
                'summary' => $summary,
            ]);

            // Dispatch execution completed event
            TestExecutionCompleted::dispatch(
                $this->commandId,
                $summary,
                now(),
                [
                    'output_file' => $this->outputFile,
                    'command_id' => $this->commandId,
                    'options' => $this->options,
                    'command' => $this->baseCommand,
                    'started_at' => $runStartedAt->toISOString(),
                    'tests_by_type' => $this->options['tests_by_type'] ?? [],
                    'estimated_total_tests' => $this->options['estimated_total_tests'] ?? count($this->testFiles),
                    'test_files' => $this->normalizeTestFiles($this->testFiles),
                ]
            );

        } catch (\Throwable $e) {
            $errorRecoveryService->handleTestFailure($this->commandId, [
                'job' => 'execute_test_suite',
                'test_files' => $this->normalizeTestFiles($this->testFiles),
                'attempt' => $this->attempts(),
            ], $e instanceof TestExecutionException ? $e : new TestExecutionException(
                $e->getMessage(),
                TestExecutionException::CATEGORY_SYSTEM,
                ['command_id' => $this->commandId],
                (int) $e->getCode(),
                $e
            ));

            Log::error("Test execution failed", [
                'command_id' => $this->commandId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Execute a test (or suite) with recovery-aware retries.
     *
     * @param mixed $testFile
     * @param ErrorRecoveryService $errorRecoveryService
     * @return array<string, mixed>
     */
    private function runTestWithRecovery(mixed $testFile, ErrorRecoveryService $errorRecoveryService): array
    {
        $attempt = 0;
        $maxAttempts = max(1, (int) ($this->options['retry_attempts'] ?? 0) + 1);
        $result = [];

        do {
            $attempt++;
            $result = $testFile === null
                ? $this->executeTestCommand()
                : $this->executeTestCommand([$testFile]);

            if (($result['status'] ?? 'passed') !== 'error') {
                $result['attempts'] = $attempt;
                return $result;
            }

            $context = [
                'test_file' => is_array($testFile)
                    ? ($testFile['file_path'] ?? 'unknown')
                    : ($testFile ?? 'full_suite'),
                'attempt' => $attempt,
            ];

            $exception = new TestExecutionException(
                $result['errors'] ?: 'Unknown test execution error.',
                TestExecutionException::CATEGORY_SYSTEM,
                $context
            );

            $recovery = $errorRecoveryService->handleTestFailure($this->commandId, $context, $exception);
            $result['recovery'] = $recovery;

            if (!($recovery['retry'] ?? false) || $attempt >= $maxAttempts) {
                $result['attempts'] = $attempt;
                return $result;
            }

            if (($delay = $recovery['delay'] ?? null) !== null) {
                sleep((int) $delay);
            }

        } while ($attempt < $maxAttempts);

        $result['attempts'] = $attempt;
        return $result;
    }

    /**
     * Execute test command using Symfony Process
     *
     * @param array|null $testFiles Specific test files to run
     * @return array Execution result
     */
    private function executeTestCommand(?array $testFiles = null): array
    {
        $startTime = microtime(true);
        
        try {
            $command = $this->buildTestCommand($testFiles);
            
            Log::debug("Executing test command", [
                'command' => $command,
                'test_files' => $testFiles,
            ]);

            $process = Process::fromShellCommandline($command, base_path());
            $process->setTimeout($this->options['timeout']);
            $process->run();

            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();

            $result = [
                'status' => $exitCode === 0 ? 'passed' : 'failed',
                'duration' => $duration,
                'output' => $output,
                'errors' => $errorOutput,
                'exit_code' => $exitCode,
                'command' => $command,
            ];

            // Parse test output for more detailed information
            $parsed = $this->parseTestOutput($output);
            $result = array_merge($result, $parsed);

            return $result;

        } catch (\Exception $e) {
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            Log::error("Test command execution failed", [
                'error' => $e->getMessage(),
                'test_files' => $testFiles,
            ]);

            return [
                'status' => 'error',
                'duration' => $duration,
                'output' => '',
                'errors' => $e->getMessage(),
                'exit_code' => -1,
                'command' => $command ?? 'unknown',
            ];
        }
    }

    /**
     * Build the test command based on options and test files
     *
     * @param array|null $testFiles Specific test files
     * @return string Command to execute
     */
    private function buildTestCommand(?array $testFiles = null): string
    {
        $command = 'php artisan test';

        // Add parallel execution if enabled
        if ($this->options['parallel']) {
            $command .= ' --parallel';
            
            if ($this->options['processes'] > 1) {
                $command .= ' --processes=' . $this->options['processes'];
            }
        }

        // Add stop on failure flag
        if ($this->options['stop_on_failure']) {
            $command .= ' --stop-on-failure';
        }

        // Add coverage flag
        if ($this->options['coverage']) {
            $command .= ' --coverage';
        }

        // Add specific test files
        if (!empty($testFiles)) {
            $testPaths = [];
            foreach ($testFiles as $testFile) {
                if (is_array($testFile) && isset($testFile['file_path'])) {
                    $testPaths[] = escapeshellarg($testFile['file_path']);
                } elseif (is_string($testFile)) {
                    $testPaths[] = escapeshellarg($testFile);
                }
            }
            
            if (!empty($testPaths)) {
                $command .= ' ' . implode(' ', $testPaths);
            }
        }

        return $command;
    }

    /**
     * Parse test output for detailed information
     *
     * @param string $output Raw test output
     * @return array Parsed test information
     */
    private function parseTestOutput(string $output): array
    {
        $parsed = [
            'tests_run' => 0,
            'tests_passed' => 0,
            'tests_failed' => 0,
            'tests_skipped' => 0,
            'assertions' => 0,
            'failures' => [],
            'warnings' => [],
        ];

        try {
            // Parse PestPHP output patterns
            if (preg_match('/Tests:\s+(\d+)\s+passed/', $output, $matches)) {
                $parsed['tests_passed'] = (int) $matches[1];
            }

            if (preg_match('/Tests:\s+\d+\s+passed.*?(\d+)\s+failed/', $output, $matches)) {
                $parsed['tests_failed'] = (int) $matches[1];
            }

            if (preg_match('/Tests:\s+\d+\s+passed.*?(\d+)\s+skipped/', $output, $matches)) {
                $parsed['tests_skipped'] = (int) $matches[1];
            }

            $parsed['tests_run'] = $parsed['tests_passed'] + $parsed['tests_failed'] + $parsed['tests_skipped'];

            if (preg_match('/Assertions:\s+(\d+)/', $output, $matches)) {
                $parsed['assertions'] = (int) $matches[1];
            }

            // Extract failure details
            if (preg_match_all('/FAILED\s+(.+?)(?=\n\n|\nTests:|$)/s', $output, $matches)) {
                $parsed['failures'] = $matches[1];
            }

            // Extract warnings
            if (preg_match_all('/Warning:\s+(.+?)(?=\n\n|\n[A-Z]:|$)/s', $output, $matches)) {
                $parsed['warnings'] = $matches[1];
            }

        } catch (\Exception $e) {
            Log::warning("Failed to parse test output", [
                'error' => $e->getMessage(),
                'output_length' => strlen($output),
            ]);
        }

        return $parsed;
    }

    /**
     * Generate execution summary from results
     *
     * @param array $results All execution results
     * @param float $totalDuration Total execution time
     * @return array Summary information
     */
    private function generateExecutionSummary(array $results, float $totalDuration): array
    {
        $summary = [
            'total_duration' => $totalDuration,
            'total_tests' => 0,
            'total_passed' => 0,
            'total_failed' => 0,
            'total_skipped' => 0,
            'total_assertions' => 0,
            'success_rate' => 0,
            'status' => 'passed',
            'failures' => [],
            'warnings' => [],
            'executed_at' => now()->toISOString(),
            'recovery_actions' => [],
        ];

        $serialDuration = 0.0;

        foreach ($results as $result) {
            $summary['total_tests'] += $result['tests_run'] ?? 0;
            $summary['total_passed'] += $result['tests_passed'] ?? 0;
            $summary['total_failed'] += $result['tests_failed'] ?? 0;
            $summary['total_skipped'] += $result['tests_skipped'] ?? 0;
            $summary['total_assertions'] += $result['assertions'] ?? 0;
            $serialDuration += $result['duration'] ?? 0.0;

            if (!empty($result['failures'])) {
                $summary['failures'] = array_merge($summary['failures'], $result['failures']);
            }

            if (!empty($result['warnings'])) {
                $summary['warnings'] = array_merge($summary['warnings'], $result['warnings']);
            }

            if (!empty($result['recovery'])) {
                $summary['recovery_actions'][] = [
                    'file' => $result['file'] ?? 'unknown',
                    'details' => $result['recovery'],
                ];
            }

            // If any result failed, mark overall status as failed
            if ($result['status'] === 'failed' || $result['status'] === 'error') {
                $summary['status'] = 'failed';
            }
        }

        // Calculate success rate
        if ($summary['total_tests'] > 0) {
            $summary['success_rate'] = round(
                ($summary['total_passed'] / $summary['total_tests']) * 100,
                2
            );
        }

        $summary['average_duration'] = $summary['total_tests'] > 0
            ? round($serialDuration / max(1, $summary['total_tests']), 2)
            : null;

        $summary['slowest_tests'] = $this->calculateSlowestTests($results);
        $summary['processes'] = $this->options['processes'] ?? null;
        $summary['parallel_efficiency'] = $this->calculateParallelEfficiency($serialDuration, $totalDuration);

        return $summary;
    }

    /**
     * Estimate the total number of tests scheduled for execution.
     *
     * @return int
     */
    private function estimateTotalTests(): int
    {
        if (!empty($this->testFiles)) {
            return count($this->testFiles);
        }

        return $this->options['estimated_total_tests'] ?? 0;
    }

    /**
     * Determine the slowest executing tests.
     *
     * @param array $results
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    private function calculateSlowestTests(array $results, int $limit = 5): array
    {
        $filtered = array_filter($results, static function ($result) {
            return isset($result['duration']) && ($result['file'] ?? null);
        });

        usort($filtered, static function ($left, $right) {
            return ($right['duration'] ?? 0) <=> ($left['duration'] ?? 0);
        });

        $filtered = array_slice($filtered, 0, $limit);

        return array_map(static function ($result) {
            return [
                'file' => $result['file'] ?? 'unknown',
                'duration' => $result['duration'] ?? 0.0,
            ];
        }, $filtered);
    }

    /**
     * Calculate the parallel efficiency percentage.
     *
     * @param float $serialDuration
     * @param float $totalDuration
     * @return float|null
     */
    private function calculateParallelEfficiency(float $serialDuration, float $totalDuration): ?float
    {
        $processes = $this->options['processes'] ?? null;

        if (!$processes || $processes <= 0 || $totalDuration <= 0.0) {
            return null;
        }

        $efficiency = ($serialDuration / ($totalDuration * $processes)) * 100;

        return round(min($efficiency, 100), 2);
    }

    /**
     * Build metadata payload for the TestExecutionStarted event.
     *
     * @return array<string, mixed>
     */
    private function buildStartMetadata(): array
    {
        return [
            'options' => $this->options,
            'estimated_total_tests' => $this->options['estimated_total_tests'] ?? count($this->testFiles),
            'tests_by_type' => $this->options['tests_by_type'] ?? [],
            'test_files' => $this->normalizeTestFiles($this->testFiles),
        ];
    }

    /**
     * Normalise test file paths from mixed arrays/strings.
     *
     * @param array<int, mixed> $testFiles
     * @return array<int, string>
     */
    private function normalizeTestFiles(array $testFiles): array
    {
        return array_values(array_filter(array_map(
            static fn ($file) => is_array($file) ? ($file['file_path'] ?? '') : (string) $file,
            $testFiles
        )));
    }

    /**
     * Handle a job failure
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        app(ErrorRecoveryService::class)->handleTestFailure($this->commandId, [
            'job' => 'execute_test_suite',
            'stage' => 'failed_handler',
            'test_files' => $this->normalizeTestFiles($this->testFiles),
        ], $exception instanceof TestExecutionException ? $exception : new TestExecutionException(
            $exception->getMessage(),
            TestExecutionException::CATEGORY_SYSTEM,
            ['command_id' => $this->commandId],
            (int) $exception->getCode(),
            $exception
        ));

        Log::error("ExecuteTestSuiteJob failed permanently", [
            'command_id' => $this->commandId,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Dispatch failure event
        $summary = [
            'status' => 'error',
            'error' => $exception->getMessage(),
            'executed_at' => now()->toISOString(),
        ];

        TestExecutionCompleted::dispatch($this->commandId, $summary, now());
    }
}
