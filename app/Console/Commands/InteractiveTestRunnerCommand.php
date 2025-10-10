<?php

namespace App\Console\Commands;

use App\Services\ProgressTrackingService;
use App\Services\TestDiscoveryService;
use App\Services\TestExecutionService;
use App\Services\TestSelectionService;
use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class InteractiveTestRunnerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'test:runner {--output-file=test-report.md : The file to write the test report to}';

    /**
     * The console command description.
     */
    protected $description = 'Interactive test runner with real-time Markdown reporting for parallel test execution';

    /**
     * Test type constants
     */
    private const UNIT_TESTS = 'unit';
    private const FEATURE_TESTS = 'feature';
    private const LIVEWIRE_TESTS = 'livewire';
    private const BROWSER_TESTS = 'browser';
    private const CUSTOM_SELECTION = 'custom';
    private const ALL_TESTS = 'all';

    /**
     * Test discovery service
     */
    private TestDiscoveryService $discoveryService;

    /**
     * Test selection service
     */
    private TestSelectionService $selectionService;

    /**
     * Test execution service
     */
    private TestExecutionService $executionService;

    /**
     * Progress tracking service
     */
    private ProgressTrackingService $progressService;

    /**
     * Selected test files for custom selection
     */
    private array $selectedTests = [];

    /**
     * Constructor - Inject services
     */
    public function __construct(
        TestDiscoveryService $discoveryService,
        TestExecutionService $executionService,
        ProgressTrackingService $progressTrackingService
    )
    {
        parent::__construct();
        
        $this->discoveryService = $discoveryService;
        $this->executionService = $executionService;
        $this->selectionService = new TestSelectionService($discoveryService, $this);
        $this->progressService = $progressTrackingService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->displayWelcomeMessage();
            
            $testSelection = $this->showMainMenu();
            
            if ($testSelection === null) {
                warning('No test selection made. Exiting...');
                return self::FAILURE;
            }

            // Handle custom selection
            if ($testSelection === self::CUSTOM_SELECTION) {
                $this->selectedTests = $this->selectCustomTests();
                
                if (empty($this->selectedTests)) {
                    warning('No tests selected. Exiting...');
                    return self::FAILURE;
                }
            }

            $this->displayTestSummary($testSelection);
            
            if (!$this->confirmExecution()) {
                info('Test execution cancelled by user.');
                return self::SUCCESS;
            }

            // Get execution options
            $executionOptions = $this->getExecutionOptions();
            
            // Start test execution
            $commandId = $this->executeTests($testSelection, $executionOptions);
            
            if ($commandId) {
                $this->displayExecutionProgress($commandId);
                return self::SUCCESS;
            } else {
                $this->error('Failed to start test execution.');
                return self::FAILURE;
            }
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("An error occurred: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Display welcome message and command information
     */
    private function displayWelcomeMessage(): void
    {
        $this->newLine();
        $this->line('╔═══════════════════════════════════════════════════════════╗');
        $this->line('║                🧪 Interactive Test Runner 🧪              ║');
        $this->line('║           EpochBridge Test Execution System              ║');
        $this->line('╚═══════════════════════════════════════════════════════════╝');
        $this->newLine();
        
        info('Welcome to the EpochBridge Interactive Test Runner!');
        $this->line('This tool helps you select and execute tests with real-time reporting.');
        $this->newLine();
    }

    /**
     * Show the main menu and get user selection
     */
    private function showMainMenu(): ?string
    {
        $testSelection = $this->getTestSelection();
        
        if (!$this->validateSelection($testSelection)) {
            warning('Invalid test selection provided.');
            return null;
        }

        return $testSelection;
    }

    /**
     * Get test type selection from user
     */
    private function getTestSelection(): string
    {
        // Get test statistics for display
        $statistics = $this->discoveryService->getTestStatistics();
        
        return select(
            label: 'Which tests would you like to run?',
            options: [
                self::ALL_TESTS => "🌍 All Tests - Run the complete test suite ({$statistics['total']} files)",
                self::UNIT_TESTS => "⚙️  Unit Tests - Test individual components and services ({$statistics['unit']} files)",
                self::FEATURE_TESTS => "🎭 Feature Tests - Test complete user workflows ({$statistics['feature']} files)",
                self::LIVEWIRE_TESTS => "⚡ Livewire Tests - Test reactive components ({$statistics['livewire']} files)",
                self::BROWSER_TESTS => "🌐 Browser Tests - Test full user interactions with Dusk ({$statistics['browser']} files)",
                self::CUSTOM_SELECTION => '🎯 Custom Selection - Choose specific test files',
            ],
            default: self::ALL_TESTS,
            hint: 'Use arrow keys to navigate, Enter to select'
        );
    }

    /**
     * Handle custom test selection
     *
     * @return array Selected test files
     */
    private function selectCustomTests(): array
    {
        return $this->selectionService->selectCustomTests();
    }

    /**
     * Validate the user's test selection
     */
    private function validateSelection(string $selection): bool
    {
        $validSelections = [
            self::ALL_TESTS,
            self::UNIT_TESTS,
            self::FEATURE_TESTS,
            self::LIVEWIRE_TESTS,
            self::BROWSER_TESTS,
            self::CUSTOM_SELECTION,
        ];

        return in_array($selection, $validSelections, true);
    }

    /**
     * Display a comprehensive summary of the test selection
     *
     * @param string $selection Test selection type
     * @return void
     */
    private function displayTestSummary(string $selection): void
    {
        $this->newLine();
        
        if ($selection === self::CUSTOM_SELECTION && !empty($this->selectedTests)) {
            // Display custom selection summary
            $this->selectionService->displayTestSummary($this->selectedTests);
        } else {
            // Display standard selection summary
            $statistics = $this->discoveryService->getTestStatistics();
            
            info('📋 Test Selection Summary');
            
            $descriptions = [
                self::ALL_TESTS => 'Complete test suite (Unit, Feature, Livewire, Browser)',
                self::UNIT_TESTS => 'Unit tests only - Fast isolated component testing',
                self::FEATURE_TESTS => 'Feature tests only - Application workflow testing',
                self::LIVEWIRE_TESTS => 'Livewire tests only - Component interaction testing',
                self::BROWSER_TESTS => 'Browser tests only - Full user interaction testing',
                self::CUSTOM_SELECTION => 'Custom selection - User-specified test files',
            ];

            $testCount = match ($selection) {
                self::ALL_TESTS => $statistics['total'],
                self::UNIT_TESTS => $statistics['unit'],
                self::FEATURE_TESTS => $statistics['feature'],
                self::LIVEWIRE_TESTS => $statistics['livewire'],
                self::BROWSER_TESTS => $statistics['browser'],
                default => 0,
            };

            $this->table(
                ['Setting', 'Value'],
                [
                    ['Test Type', ucfirst(str_replace('_', ' ', $selection))],
                    ['Description', $descriptions[$selection] ?? 'Unknown'],
                    ['Test Count', $testCount],
                    ['Output File', $this->option('output-file')],
                    ['Execution Mode', 'Interactive (Phase 2 implementation)'],
                ]
            );
        }
        
        $this->newLine();
    }

    /**
     * Confirm execution with the user
     */
    private function confirmExecution(): bool
    {
        return confirm(
            label: 'Proceed with test execution?',
            default: true,
            hint: 'This will start the test execution process'
        );
    }

    /**
     * Get execution options from user
     *
     * @return array Execution options
     */
    private function getExecutionOptions(): array
    {
        $this->newLine();
        info('⚙️  Execution Options');
        
        $options = [];
        
        // Parallel execution option
        $options['parallel'] = confirm(
            label: 'Enable parallel execution?',
            default: true,
            hint: 'Run tests in parallel for faster execution'
        );

        if ($options['parallel']) {
            $options['processes'] = (int) select(
                label: 'Number of parallel processes',
                options: [
                    '2' => '2 processes (conservative)',
                    '4' => '4 processes (recommended)',
                    '6' => '6 processes (aggressive)',
                    '8' => '8 processes (maximum)',
                ],
                default: '4',
                hint: 'More processes = faster execution but higher resource usage'
            );
        }

        // Stop on failure option
        $options['stop_on_failure'] = confirm(
            label: 'Stop on first failure?',
            default: false,
            hint: 'Stop execution when the first test fails'
        );

        // Coverage option
        $options['coverage'] = confirm(
            label: 'Generate test coverage report?',
            default: false,
            hint: 'Enable code coverage analysis (slower execution)'
        );

        // Timeout option
        $timeoutMinutes = (int) select(
            label: 'Execution timeout',
            options: [
                '5' => '5 minutes (quick tests)',
                '10' => '10 minutes (recommended)',
                '15' => '15 minutes (comprehensive)',
                '30' => '30 minutes (extended)',
            ],
            default: '10',
            hint: 'Maximum time to wait for test completion'
        );
        
        $options['timeout'] = $timeoutMinutes * 60; // Convert to seconds

        return $options;
    }

    /**
     * Execute tests based on selection and options
     *
     * @param string $testSelection Test selection type
     * @param array $options Execution options
     * @return string|null Command ID or null on failure
     */
    private function executeTests(string $testSelection, array $options): ?string
    {
        try {
            $this->newLine();
            info('🚀 Starting Test Execution');
            $this->newLine();

            // Determine test files to execute
            $testFiles = $this->getTestFilesForExecution($testSelection);
            $outputFile = $this->option('output-file');

            $this->line("Output file: <fg=cyan>{$outputFile}</>");
            $this->line("Execution mode: " . ($options['parallel'] ? 'Parallel' : 'Sequential'));
            $this->line("Test files: " . count($testFiles) . " files");
            $this->newLine();

            $progressContext = $this->buildProgressContext($testSelection, $testFiles);

            $options = array_merge(
                $options,
                [
                    'estimated_total_tests' => $progressContext['estimated_total_tests'],
                    'tests_by_type' => $progressContext['tests_by_type'],
                    'test_files' => $progressContext['test_files'],
                ]
            );

            app()->instance('interactive-test-runner.output', $this->output);

            // Start execution via service
            $commandId = $this->executionService->startTestExecution(
                $testFiles,
                $outputFile,
                $options
            );

            info("✓ Test execution started with ID: {$commandId}");
            
            return $commandId;

        } catch (\Exception $e) {
            app()->forgetInstance('interactive-test-runner.output');
            $this->error("Failed to start test execution: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Get test files for execution based on selection
     *
     * @param string $testSelection Test selection type
     * @return array Test files to execute
     */
    private function getTestFilesForExecution(string $testSelection): array
    {
        if ($testSelection === self::CUSTOM_SELECTION) {
            return $this->selectedTests;
        }

        if ($testSelection === self::ALL_TESTS) {
            return []; // Empty array means run all tests
        }

        // Get tests for specific category
        $discovered = $this->discoveryService->discoverAllTests();
        
        return match ($testSelection) {
            self::UNIT_TESTS => $discovered['unit'] ?? [],
            self::FEATURE_TESTS => $discovered['feature'] ?? [],
            self::LIVEWIRE_TESTS => $discovered['livewire'] ?? [],
            self::BROWSER_TESTS => $discovered['browser'] ?? [],
            default => [],
        };
    }

    /**
     * Build progress tracking context for the given selection.
     *
     * @param string $selection
     * @param array<int, mixed> $testFiles
     * @return array{estimated_total_tests:int, tests_by_type:array<string,int>, test_files:array<int,string>}
     */
    private function buildProgressContext(string $selection, array $testFiles): array
    {
        $statistics = $this->discoveryService->getTestStatistics();

        $estimatedTotal = !empty($testFiles)
            ? count($testFiles)
            : (int) ($statistics['total'] ?? 0);

        $testsByType = [
            'unit' => 0,
            'feature' => 0,
            'livewire' => 0,
            'browser' => 0,
        ];

        if (!empty($testFiles)) {
            foreach ($testFiles as $file) {
                $path = is_array($file) ? ($file['file_path'] ?? '') : $file;
                $type = $this->progressService->determineTestType($path);
                $testsByType[$type] = ($testsByType[$type] ?? 0) + 1;
            }
        } elseif ($selection === self::ALL_TESTS) {
            $testsByType['unit'] = (int) ($statistics['unit'] ?? 0);
            $testsByType['feature'] = (int) ($statistics['feature'] ?? 0);
            $testsByType['livewire'] = (int) ($statistics['livewire'] ?? 0);
            $testsByType['browser'] = (int) ($statistics['browser'] ?? 0);
        }

        $normalizedFiles = array_map(
            static fn ($file) => is_array($file) ? ($file['file_path'] ?? '') : $file,
            $testFiles
        );

        return [
            'estimated_total_tests' => $estimatedTotal,
            'tests_by_type' => $testsByType,
            'test_files' => array_values(array_filter($normalizedFiles)),
        ];
    }

    /**
     * Display execution progress and wait for completion.
     */
    private function displayExecutionProgress(string $commandId): void
    {
        $this->progressService->setActiveCommand($commandId);
        $this->progressService->registerOutput($this->output);
        app()->instance('interactive-test-runner.output', $this->output);

        $this->displayProgressInterface();
        $this->handleProgressUpdates($commandId);

        app()->forgetInstance('interactive-test-runner.output');
    }

    /**
     * Render the initial progress interface header.
     */
    private function displayProgressInterface(): void
    {
        $this->newLine();
        $this->line('📊 <fg=cyan>Real-time Test Execution Progress</>');
        $this->line('Press Ctrl+C to cancel execution');
        $this->newLine();
    }

    /**
     * Handle progress updates using a non-blocking polling loop.
     */
    private function handleProgressUpdates(string $commandId): void
    {
        $pollIntervalMicroseconds = 150000; // ~6Hz refresh

        while (true) {
            try {
                $progress = $this->executionService->trackProgress($commandId);

                if (!$progress) {
                    warning('Progress tracking unavailable for this execution.');
                    break;
                }

                $this->refreshProgressDisplay($commandId);

                $status = $progress['status'] ?? 'running';
                if (in_array($status, ['completed', 'failed', 'cancelled', 'error'], true)) {
                    break;
                }

                usleep($pollIntervalMicroseconds);
            } catch (\Exception $exception) {
                $this->error("Error monitoring progress: {$exception->getMessage()}");
                break;
            }
        }

        $this->refreshProgressDisplay($commandId, true);
        $this->showExecutionSummary($commandId);
    }

    /**
     * Refresh the current progress display with the latest statistics.
     */
    private function refreshProgressDisplay(string $commandId, bool $force = false): void
    {
        $this->progressService->displayCurrentStatus($this->output, $commandId, $force);
    }

    /**
     * Format duration for human reading
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 1) {
            return number_format($seconds, 2) . 's';
        }

        if ($seconds < 60) {
            return number_format($seconds, 1) . 's';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return $minutes . 'm ' . number_format($remainingSeconds, 1) . 's';
    }

    /**
     * Output the final execution summary once testing completes.
     */
    private function showExecutionSummary(string $commandId): void
    {
        $this->progressService->registerOutput($this->output);
        $this->progressService->displayCurrentStatus($this->output, $commandId, true);
        $this->newLine();

        $report = $this->executionService->getExecutionReport($commandId);
        $statistics = $this->progressService->getStatistics($commandId);
        $summary = $report['summary'] ?? $statistics['summary'] ?? [];

        $status = $summary['status'] ?? 'unknown';
        $statusLabel = match ($status) {
            'passed' => '<fg=green>✅ PASSED</>',
            'failed', 'error' => '<fg=red>❌ FAILED</>',
            default => '<fg=yellow>ℹ️ COMPLETED</>',
        };

        $this->line(match ($status) {
            'passed' => '<fg=green>✅ Test Execution Completed Successfully!</>',
            'failed', 'error' => '<fg=red>❌ Test Execution Completed with Issues</>',
            default => '<fg=yellow>ℹ️ Test Execution Completed</>',
        });

        $this->newLine();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Status', $statusLabel],
                ['Total Tests', $summary['total_tests'] ?? ($statistics['completed'] ?? 0)],
                ['Passed', $summary['total_passed'] ?? ($statistics['passed'] ?? 0)],
                ['Failed', $summary['total_failed'] ?? ($statistics['failed'] ?? 0)],
                ['Skipped', $summary['total_skipped'] ?? ($statistics['skipped'] ?? 0)],
                ['Success Rate', ($summary['success_rate'] ?? 0) . '%'],
                ['Duration', $this->formatDuration($summary['total_duration'] ?? ($statistics['elapsed'] ?? 0))],
                ['Average Test Duration', $this->formatDuration($statistics['average_duration'] ?? 0)],
                ['Output File', $report['output_file'] ?? 'N/A'],
            ]
        );

        $this->newLine();

        if (!empty($statistics['by_type'])) {
            $this->line('Breakdown by Test Type:');
            $rows = [];

            foreach ($statistics['by_type'] as $type => $data) {
                $rows[] = [
                    ucfirst($type),
                    $data['total'],
                    $data['completed'],
                    $data['passed'],
                    $data['failed'],
                    $data['skipped'],
                ];
            }

            $this->table(
                ['Type', 'Total', 'Completed', 'Passed', 'Failed', 'Skipped'],
                $rows
            );

            $this->newLine();
        }

        if (!empty($summary['failures'])) {
            warning('Test Failures:');
            foreach (array_slice($summary['failures'], 0, 5) as $failure) {
                $this->line("  • " . trim($failure));
            }
            if (count($summary['failures']) > 5) {
                $this->line('  ... additional failures omitted');
            }
            $this->newLine();
        }

        if (!empty($summary['warnings'])) {
            $this->line('<fg=yellow>Warnings:</>');
            foreach (array_slice($summary['warnings'], 0, 3) as $warningMessage) {
                $this->line("  • " . trim($warningMessage));
            }
            if (count($summary['warnings']) > 3) {
                $this->line('  ... additional warnings omitted');
            }
            $this->newLine();
        }

        if (!empty($summary['recovery_actions'])) {
            $this->line('<fg=cyan>Recovery Actions:</>');
            foreach ($summary['recovery_actions'] as $recovery) {
                $fileLabel = $recovery['file'] ?? 'unknown';
                $details = $recovery['details'] ?? [];
                $message = is_array($details)
                    ? ($details['recovery']['message'] ?? $details['diagnosis']['reason'] ?? 'Recovery attempted')
                    : (string) $details;
                $this->line("  • {$fileLabel}: {$message}");
            }
            $this->newLine();
        }

        if (isset($report['output_file'])) {
            info("📄 Full report available at: {$report['output_file']}");
        }
    }
}
