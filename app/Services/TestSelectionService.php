<?php

namespace App\Services;

use Illuminate\Console\Command;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\warning;

class TestSelectionService
{
    /**
     * Test discovery service
     */
    private TestDiscoveryService $discoveryService;

    /**
     * Console command instance for output
     */
    private Command $command;

    /**
     * Constructor
     *
     * @param TestDiscoveryService $discoveryService
     * @param Command $command
     */
    public function __construct(TestDiscoveryService $discoveryService, Command $command)
    {
        $this->discoveryService = $discoveryService;
        $this->command = $command;
    }

    /**
     * Display hierarchical file tree of test files
     *
     * @param array $tests Array of test file information
     * @return void
     */
    public function displayFileTree(array $tests): void
    {
        $this->command->newLine();
        info('📁 Test File Structure');
        $this->command->newLine();

        $grouped = $this->groupTestsByDirectory($tests);
        
        foreach ($grouped as $directory => $directoryTests) {
            $this->command->line("📂 <fg=cyan>{$directory}</>");
            
            foreach ($directoryTests as $test) {
                $icon = $this->getTestIcon($test['category']);
                $this->command->line("  {$icon} {$test['filename']}");
            }
            
            $this->command->newLine();
        }
    }

    /**
     * Handle custom test selection with interactive interface
     *
     * @return array<int, array> Selected test files
     */
    public function selectCustomTests(): array
    {
        $this->command->newLine();
        info('🎯 Custom Test Selection');
        $this->command->line('Choose your preferred selection method:');
        $this->command->newLine();

        $selectionMethod = select(
            label: 'How would you like to select tests?',
            options: [
                'category' => '📋 By Category - Select test categories first',
                'search' => '🔍 Search - Find tests by name or path',
                'browse' => '📁 Browse - Browse the file tree',
                'manual' => '✏️  Manual - Enter test paths manually',
            ],
            default: 'category',
            hint: 'Choose the most convenient method for your needs'
        );

        return match ($selectionMethod) {
            'category' => $this->selectByCategory(),
            'search' => $this->selectBySearch(),
            'browse' => $this->selectByBrowsing(),
            'manual' => $this->selectManually(),
            default => [],
        };
    }

    /**
     * Select tests by category first, then specific files
     *
     * @return array<int, array> Selected test files
     */
    private function selectByCategory(): array
    {
        $statistics = $this->discoveryService->getTestStatistics();
        
        // Show statistics first
        $this->displayTestStatistics($statistics);
        
        $selectedCategories = multiselect(
            label: 'Select test categories',
            options: [
                'unit' => "⚙️  Unit Tests ({$statistics['unit']} files)",
                'feature' => "🎭 Feature Tests ({$statistics['feature']} files)",
                'livewire' => "⚡ Livewire Tests ({$statistics['livewire']} files)",
                'browser' => "🌐 Browser Tests ({$statistics['browser']} files)",
            ],
            hint: 'Use space to select, enter to continue',
            required: true
        );

        if (empty($selectedCategories)) {
            warning('No categories selected.');
            return [];
        }

        // Ask if they want all tests from selected categories or specific files
        $includeAll = confirm(
            label: 'Include all tests from selected categories?',
            default: true,
            hint: 'If No, you can choose specific files from each category'
        );

        if ($includeAll) {
            return $this->getTestsFromCategories($selectedCategories);
        }

        return $this->selectSpecificTestsFromCategories($selectedCategories);
    }

    /**
     * Select tests using search functionality
     *
     * @return array<int, array> Selected test files
     */
    private function selectBySearch(): array
    {
        $selectedTests = [];
        $allTests = $this->discoveryService->discoverAllTests()['all'];
        
        $this->command->line('🔍 Search for tests by typing part of the name or path.');
        $this->command->line('You can add multiple tests one by one.');
        $this->command->newLine();

        do {
            $searchQuery = search(
                label: 'Search for a test',
                options: function (string $value) use ($allTests) {
                    if (strlen($value) < 2) {
                        return [];
                    }
                    
                    $results = [];
                    foreach ($allTests as $test) {
                        if (str_contains(strtolower($test['display_name']), strtolower($value)) ||
                            str_contains(strtolower($test['relative_path']), strtolower($value))) {
                            $results[$test['relative_path']] = $test['display_name'];
                        }
                    }
                    
                    return array_slice($results, 0, 10); // Limit to 10 results
                },
                placeholder: 'Type to search...',
                hint: 'Start typing a test name or path (minimum 2 characters)'
            );

            if ($searchQuery) {
                // Find the full test information
                $selectedTest = collect($allTests)->first(fn($test) => $test['relative_path'] === $searchQuery);
                
                if ($selectedTest) {
                    $selectedTests[] = $selectedTest;
                    info("✓ Added: {$selectedTest['display_name']}");
                }
            }

            $continueSearching = confirm(
                label: 'Add another test?',
                default: false
            );

        } while ($continueSearching);

        return $selectedTests;
    }

    /**
     * Select tests by browsing the file tree
     *
     * @return array<int, array> Selected test files
     */
    private function selectByBrowsing(): array
    {
        $allTests = $this->discoveryService->discoverAllTests()['all'];
        $grouped = $this->groupTestsByDirectory($allTests);
        
        // First, select directories
        $directoryOptions = [];
        foreach ($grouped as $directory => $tests) {
            $directoryOptions[$directory] = "{$directory} ({" . count($tests) . "} tests)";
        }

        $selectedDirectories = multiselect(
            label: 'Select directories to explore',
            options: $directoryOptions,
            hint: 'Use space to select, enter to continue',
            required: true
        );

        if (empty($selectedDirectories)) {
            return [];
        }

        // Now select specific tests from chosen directories
        $availableTests = [];
        foreach ($selectedDirectories as $directory) {
            $availableTests = array_merge($availableTests, $grouped[$directory]);
        }

        $testOptions = [];
        foreach ($availableTests as $test) {
            $icon = $this->getTestIcon($test['category']);
            $testOptions[$test['relative_path']] = "{$icon} {$test['display_name']}";
        }

        $selectedTestPaths = multiselect(
            label: 'Select specific test files',
            options: $testOptions,
            hint: 'Use space to select, enter to continue'
        );

        // Return full test information for selected tests
        return array_filter($availableTests, fn($test) => in_array($test['relative_path'], $selectedTestPaths));
    }

    /**
     * Manual test path entry
     *
     * @return array<int, array> Selected test files
     */
    private function selectManually(): array
    {
        $this->command->line('✏️  Enter test file paths manually.');
        $this->command->line('You can enter relative paths like "Feature/Auth/LoginTest.php"');
        $this->command->newLine();

        $selectedTests = [];
        $allTests = $this->discoveryService->discoverAllTests()['all'];

        do {
            $testPath = $this->command->ask('Enter test file path (relative to tests/ directory)');
            
            if ($testPath) {
                // Find matching test
                $matchingTest = collect($allTests)->first(function ($test) use ($testPath) {
                    return str_contains($test['relative_path'], $testPath) ||
                           str_contains($test['filename'], $testPath);
                });

                if ($matchingTest) {
                    $selectedTests[] = $matchingTest;
                    info("✓ Added: {$matchingTest['display_name']}");
                } else {
                    warning("❌ Test not found: {$testPath}");
                }
            }

            $continueAdding = confirm(
                label: 'Add another test?',
                default: false
            );

        } while ($continueAdding);

        return $selectedTests;
    }

    /**
     * Validate custom test selection
     *
     * @param array $selectedTests Selected test files
     * @return bool True if selection is valid
     */
    public function validateCustomSelection(array $selectedTests): bool
    {
        if (empty($selectedTests)) {
            warning('No tests selected.');
            return false;
        }

        // Validate each test file exists and is readable
        foreach ($selectedTests as $test) {
            if (!file_exists($test['file_path']) || !is_readable($test['file_path'])) {
                warning("Test file not accessible: {$test['relative_path']}");
                return false;
            }
        }

        return true;
    }

    /**
     * Build PestPHP command arguments for selected tests
     *
     * @param array $selectedTests Selected test files
     * @param array $options Additional options
     * @return string Command arguments
     */
    public function buildTestCommand(array $selectedTests, array $options = []): string
    {
        $command = 'php artisan test';

        // Add parallel flag if requested
        if ($options['parallel'] ?? false) {
            $command .= ' --parallel';
        }

        // Add specific test files
        if (!empty($selectedTests)) {
            $testPaths = array_map(fn($test) => $test['file_path'], $selectedTests);
            $command .= ' ' . implode(' ', array_map('escapeshellarg', $testPaths));
        }

        // Add other options
        if ($options['stop_on_failure'] ?? false) {
            $command .= ' --stop-on-failure';
        }

        if ($options['coverage'] ?? false) {
            $command .= ' --coverage';
        }

        return $command;
    }

    /**
     * Display test selection summary
     *
     * @param array $selectedTests Selected test files
     * @return void
     */
    public function displayTestSummary(array $selectedTests): void
    {
        $this->command->newLine();
        info('📋 Test Selection Summary');
        
        if (empty($selectedTests)) {
            warning('No tests selected.');
            return;
        }

        $categoryCounts = [
            'unit' => 0,
            'feature' => 0,
            'livewire' => 0,
            'browser' => 0,
        ];

        foreach ($selectedTests as $test) {
            $categoryCounts[$test['category']]++;
        }

        $this->command->table(
            ['Category', 'Count', 'Files'],
            [
                ['Unit Tests', $categoryCounts['unit'], $this->getTestFilesByCategory($selectedTests, 'unit')],
                ['Feature Tests', $categoryCounts['feature'], $this->getTestFilesByCategory($selectedTests, 'feature')],
                ['Livewire Tests', $categoryCounts['livewire'], $this->getTestFilesByCategory($selectedTests, 'livewire')],
                ['Browser Tests', $categoryCounts['browser'], $this->getTestFilesByCategory($selectedTests, 'browser')],
                ['Total', count($selectedTests), ''],
            ]
        );

        $this->command->newLine();
    }

    /**
     * Group tests by directory
     *
     * @param array $tests Array of test information
     * @return array<string, array> Tests grouped by directory
     */
    private function groupTestsByDirectory(array $tests): array
    {
        $grouped = [];

        foreach ($tests as $test) {
            $directory = $test['directory'] === '.' ? 'Root' : $test['directory'];
            
            if (!isset($grouped[$directory])) {
                $grouped[$directory] = [];
            }
            
            $grouped[$directory][] = $test;
        }

        ksort($grouped);
        return $grouped;
    }

    /**
     * Get icon for test category
     *
     * @param string $category Test category
     * @return string Icon
     */
    private function getTestIcon(string $category): string
    {
        return match ($category) {
            'unit' => '⚙️',
            'feature' => '🎭',
            'livewire' => '⚡',
            'browser' => '🌐',
            default => '📄',
        };
    }

    /**
     * Get tests from selected categories
     *
     * @param array $categories Selected category names
     * @return array<int, array> Test files from categories
     */
    private function getTestsFromCategories(array $categories): array
    {
        $discovered = $this->discoveryService->discoverAllTests();
        $tests = [];

        foreach ($categories as $category) {
            if (isset($discovered[$category])) {
                $tests = array_merge($tests, $discovered[$category]);
            }
        }

        return $tests;
    }

    /**
     * Select specific tests from categories
     *
     * @param array $categories Selected category names
     * @return array<int, array> Selected test files
     */
    private function selectSpecificTestsFromCategories(array $categories): array
    {
        $discovered = $this->discoveryService->discoverAllTests();
        $selectedTests = [];

        foreach ($categories as $category) {
            if (!isset($discovered[$category]) || empty($discovered[$category])) {
                continue;
            }

            $categoryTests = $discovered[$category];
            $testOptions = [];
            
            foreach ($categoryTests as $test) {
                $testOptions[$test['relative_path']] = $test['display_name'];
            }

            $categoryIcon = $this->getTestIcon($category);
            $selectedTestPaths = multiselect(
                label: "{$categoryIcon} Select " . ucfirst($category) . " tests",
                options: $testOptions,
                hint: 'Use space to select, enter to continue'
            );

            // Add selected tests
            foreach ($categoryTests as $test) {
                if (in_array($test['relative_path'], $selectedTestPaths)) {
                    $selectedTests[] = $test;
                }
            }
        }

        return $selectedTests;
    }

    /**
     * Display test statistics
     *
     * @param array $statistics Test count statistics
     * @return void
     */
    private function displayTestStatistics(array $statistics): void
    {
        $this->command->newLine();
        info('📊 Test Statistics');
        
        $this->command->table(
            ['Category', 'Count'],
            [
                ['⚙️  Unit Tests', $statistics['unit']],
                ['🎭 Feature Tests', $statistics['feature']],
                ['⚡ Livewire Tests', $statistics['livewire']],
                ['🌐 Browser Tests', $statistics['browser']],
                ['📊 Total', $statistics['total']],
            ]
        );
    }

    /**
     * Get test files by category for display
     *
     * @param array $tests Selected tests
     * @param string $category Category to filter
     * @return string Comma-separated file names
     */
    private function getTestFilesByCategory(array $tests, string $category): string
    {
        $categoryTests = array_filter($tests, fn($test) => $test['category'] === $category);
        
        if (empty($categoryTests)) {
            return '-';
        }

        $filenames = array_map(fn($test) => $test['filename'], $categoryTests);
        
        if (count($filenames) > 3) {
            return implode(', ', array_slice($filenames, 0, 3)) . ', ... +' . (count($filenames) - 3);
        }

        return implode(', ', $filenames);
    }
}