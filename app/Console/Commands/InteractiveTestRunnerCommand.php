<?php

namespace App\Console\Commands;

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

            $this->displaySelectionSummary($testSelection);
            
            if (!$this->confirmExecution()) {
                info('Test execution cancelled by user.');
                return self::SUCCESS;
            }

            info('Test execution would start here (Phase 1 implementation complete)');
            info("Selected tests: {$testSelection}");
            info("Output file: {$this->option('output-file')}");
            
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
        return select(
            label: 'Which tests would you like to run?',
            options: [
                self::ALL_TESTS => '🌍 All Tests - Run the complete test suite',
                self::UNIT_TESTS => '⚙️  Unit Tests - Test individual components and services',
                self::FEATURE_TESTS => '🎭 Feature Tests - Test complete user workflows',
                self::LIVEWIRE_TESTS => '⚡ Livewire Tests - Test reactive components',
                self::BROWSER_TESTS => '🌐 Browser Tests - Test full user interactions with Dusk',
                self::CUSTOM_SELECTION => '🎯 Custom Selection - Choose specific test files',
            ],
            default: self::ALL_TESTS,
            hint: 'Use arrow keys to navigate, Enter to select'
        );
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
     * Display a summary of the user's selection
     */
    private function displaySelectionSummary(string $selection): void
    {
        $this->newLine();
        info('📋 Test Selection Summary');
        
        $descriptions = [
            self::ALL_TESTS => 'Complete test suite (Unit, Feature, Livewire, Browser)',
            self::UNIT_TESTS => 'Unit tests only - Fast isolated component testing',
            self::FEATURE_TESTS => 'Feature tests only - Application workflow testing',
            self::LIVEWIRE_TESTS => 'Livewire tests only - Component interaction testing',
            self::BROWSER_TESTS => 'Browser tests only - Full user interaction testing',
            self::CUSTOM_SELECTION => 'Custom selection - User-specified test files',
        ];

        $this->table(
            ['Setting', 'Value'],
            [
                ['Test Type', ucfirst(str_replace('_', ' ', $selection))],
                ['Description', $descriptions[$selection] ?? 'Unknown'],
                ['Output File', $this->option('output-file')],
                ['Execution Mode', 'Interactive (Phase 1 implementation)'],
            ]
        );
        
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