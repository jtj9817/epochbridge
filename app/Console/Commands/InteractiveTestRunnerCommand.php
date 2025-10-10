<?php

namespace App\Console\Commands;

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
     * Selected test files for custom selection
     */
    private array $selectedTests = [];

    /**
     * Constructor - Inject services
     */
    public function __construct(TestDiscoveryService $discoveryService, TestExecutionService $executionService)
    {
        parent::__construct();
        
        $this->discoveryService = $discoveryService;
        $this->executionService = $executionService;
        $this->selectionService = new TestSelectionService($discoveryService, $this);
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

            // Start execution via service
            $commandId = $this->executionService->startTestExecution(
                $testFiles,
                $outputFile,
                $options
            );

            info("✓ Test execution started with ID: {$commandId}");
            
            return $commandId;

        } catch (\Exception $e) {
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
     * Display execution progress and wait for completion
     *
     * @param string $commandId Command identifier
     * @return void
     */
    private function displayExecutionProgress(string $commandId): void
    {
        $this->newLine();
        info('📊 Test Execution Progress');
        $this->line('Press Ctrl+C to cancel execution');
        $this->newLine();

        $startTime = now();
        $lastUpdate = 0;

        // Progress monitoring loop
        while (true) {
            try {
                $progress = $this->executionService->trackProgress($commandId);
                
                if (!$progress) {
                    warning('Lost connection to test execution.');
                    break;
                }

                // Display progress every 5 seconds or when significant change occurs
                if (time() - $lastUpdate >= 5 || $progress['status'] !== 'running') {
                    $this->displayProgressUpdate($progress, $startTime);
                    $lastUpdate = time();
                }

                // Check if execution completed
                if (in_array($progress['status'], ['completed', 'failed', 'cancelled'])) {
                    $this->displayExecutionComplete($commandId, $progress);
                    break;
                }

                // Sleep for 1 second before next check
                sleep(1);

            } catch (\Exception $e) {
                $this->error("Error monitoring progress: {$e->getMessage()}");
                break;
            }
        }
    }

    /**
     * Display a progress update
     *
     * @param array $progress Progress information
     * @param \Carbon\Carbon $startTime Execution start time
     * @return void
     */
    private function displayProgressUpdate(array $progress, \Carbon\Carbon $startTime): void
    {
        $elapsed = $startTime->diffForHumans(null, true);
        $percentage = number_format($progress['progress'] ?? 0, 1);
        $completed = $progress['completed_tests'] ?? 0;
        $total = $progress['total_tests'] ?? 0;
        $status = $progress['status'] ?? 'unknown';
        $eta = $progress['eta'] ?? 'calculating...';

        $this->line("Status: <fg=cyan>{$status}</> | Progress: <fg=green>{$percentage}%</> ({$completed}/{$total}) | Elapsed: {$elapsed} | ETA: {$eta}");
    }

    /**
     * Display execution completion summary
     *
     * @param string $commandId Command identifier
     * @param array $progress Final progress information
     * @return void
     */
    private function displayExecutionComplete(string $commandId, array $progress): void
    {
        $this->newLine();
        
        $report = $this->executionService->getExecutionReport($commandId);
        
        if (!$report) {
            warning('Could not retrieve execution report.');
            return;
        }

        $summary = $report['summary'] ?? [];
        $status = $summary['status'] ?? 'unknown';

        // Display header based on status
        if ($status === 'passed') {
            info('✅ Test Execution Completed Successfully!');
        } else {
            warning('❌ Test Execution Completed with Issues');
        }

        $this->newLine();

        // Display summary table
        $this->table(
            ['Metric', 'Value'],
            [
                ['Status', $status === 'passed' ? '✅ PASSED' : '❌ FAILED'],
                ['Total Tests', $summary['total_tests'] ?? 0],
                ['Passed', $summary['total_passed'] ?? 0],
                ['Failed', $summary['total_failed'] ?? 0],
                ['Skipped', $summary['total_skipped'] ?? 0],
                ['Success Rate', ($summary['success_rate'] ?? 0) . '%'],
                ['Duration', $this->formatDuration($summary['total_duration'] ?? 0)],
                ['Output File', $report['output_file'] ?? 'N/A'],
            ]
        );

        // Display failures if any
        if (!empty($summary['failures'])) {
            $this->newLine();
            warning('Test Failures:');
            foreach (array_slice($summary['failures'], 0, 5) as $failure) {
                $this->line("  • " . trim($failure));
            }
            
            if (count($summary['failures']) > 5) {
                $this->line("  ... and " . (count($summary['failures']) - 5) . " more failures");
            }
        }

        // Display warnings if any
        if (!empty($summary['warnings'])) {
            $this->newLine();
            $this->line('<fg=yellow>Warnings:</>');
            foreach (array_slice($summary['warnings'], 0, 3) as $warning) {
                $this->line("  • " . trim($warning));
            }
            
            if (count($summary['warnings']) > 3) {
                $this->line("  ... and " . (count($summary['warnings']) - 3) . " more warnings");
            }
        }

        $this->newLine();
        info("📄 Full report available at: {$report['output_file']}");
    }

    /**
     * Format duration for human reading
     *
     * @param float $seconds Duration in seconds
     * @return string Formatted duration
     */
    private function formatDuration(float $seconds): string
    {
        if ($seconds < 1) {
            return number_format($seconds, 2) . 's';
        } elseif ($seconds < 60) {
            return number_format($seconds, 1) . 's';
        } else {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . 'm ' . number_format($remainingSeconds, 1) . 's';
        }
    }
}