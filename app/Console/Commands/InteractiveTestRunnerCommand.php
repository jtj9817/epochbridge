<?php

namespace App\Console\Commands;

use App\Services\TestDiscoveryService;
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
     * Selected test files for custom selection
     */
    private array $selectedTests = [];

    /**
     * Constructor - Inject services
     */
    public function __construct(TestDiscoveryService $discoveryService)
    {
        parent::__construct();
        
        $this->discoveryService = $discoveryService;
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

            info('Test execution would start here (Phase 2 implementation complete)');
            info("Selected test type: {$testSelection}");
            info("Output file: {$this->option('output-file')}");
            
            if (!empty($this->selectedTests)) {
                info("Custom tests selected: " . count($this->selectedTests) . " files");
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
}