# Automating Laravel Testing with Jobs and Commands

> **Note (September 2026):** the `Livewire` test suite/category referenced in these examples was removed with the Livewire runtime (`19780dd`, September 2026). The test runner now discovers Unit, Feature, and Browser suites only, and React component tests run under Vitest (`npm run test:run`) rather than Pest. The job/command patterns below remain valid.

## Documentation References
- [Testing](https://laravel.com/docs/12.x/testing)
- [Artisan Console](https://laravel.com/docs/12.x/artisan)
- [Queues](https://laravel.com/docs/12.x/queues)

## Implementation Approach

Here's a complete implementation of your automated testing system:

### 1. Create the Artisan Command

```php
<?php

namespace App\Console\Commands;

use App\Jobs\RunTestSuiteJob;
use Illuminate\Console\Command;

class RunAutomatedTests extends Command
{
    protected $signature = 'tests:automate 
                            {--type=all : Type of tests to run (all, unit, feature, livewire)}
                            {--tests=* : Specific test classes to run}
                            {--parallel : Run tests in parallel}
                            {--sync : Run synchronously instead of queued}';

    protected $description = 'Automate test execution with result reporting';

    public function handle()
    {
        $type = $this->option('type');
        $specificTests = $this->option('tests');
        $parallel = $this->option('parallel');
        $sync = $this->option('sync');

        $this->info('🚀 Starting automated test suite...');
        $this->info("Test type: {$type}");
        
        if ($parallel) {
            $this->info('Running in parallel mode');
        }

        $job = new RunTestSuiteJob([
            'type' => $type,
            'specific_tests' => $specificTests,
            'parallel' => $parallel,
            'requested_by' => 'console',
            'requested_at' => now(),
        ]);

        if ($sync) {
            // Run synchronously for immediate feedback
            $job->handle();
            $this->info('✅ Tests completed! Check storage/test-reports/ for results.');
        } else {
            // Dispatch to queue
            dispatch($job);
            $this->info('✅ Test job dispatched to queue!');
        }

        return Command::SUCCESS;
    }
}
```

### 2. Create the Test Execution Job

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class RunTestSuiteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function handle()
    {
        $startTime = microtime(true);
        
        // Build the test command
        $command = $this->buildTestCommand();
        
        // Execute tests
        $result = Process::run($command);
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        // Parse test results
        $parsedResults = $this->parseTestOutput($result->output());
        
        // Generate markdown report
        $markdown = $this->generateMarkdownReport($parsedResults, $duration);
        
        // Save report
        $filename = 'test-report-' . now()->format('Y-m-d-His') . '.md';
        Storage::disk('local')->put("test-reports/{$filename}", $markdown);
        
        // Log summary
        \Log::info('Test Suite Completed', [
            'duration' => $duration,
            'tests_run' => $parsedResults['tests_run'],
            'assertions' => $parsedResults['assertions'],
            'failures' => $parsedResults['failures'],
            'report' => $filename,
        ]);
        
        // Optionally send notification
        $this->sendNotification($parsedResults, $filename);
    }

    protected function buildTestCommand(): string
    {
        $command = 'php artisan test';
        
        // Add parallel flag if requested
        if ($this->config['parallel'] ?? false) {
            $command .= ' --parallel';
        }
        
        // Filter by test type
        switch ($this->config['type']) {
            case 'unit':
                $command .= ' --testsuite=Unit';
                break;
            case 'feature':
                $command .= ' --testsuite=Feature';
                break;
            case 'livewire':
                // Assuming you have a Livewire test suite configured
                $command .= ' --testsuite=Livewire';
                break;
        }
        
        // Add specific test classes if provided
        if (!empty($this->config['specific_tests'])) {
            $command .= ' --filter=' . implode('|', $this->config['specific_tests']);
        }
        
        // Add verbose output for better parsing
        $command .= ' --testdox';
        
        return $command;
    }

    protected function parseTestOutput(string $output): array
    {
        // Parse PHPUnit/PEST output
        $data = [
            'tests_run' => 0,
            'assertions' => 0,
            'failures' => 0,
            'errors' => 0,
            'warnings' => 0,
            'skipped' => 0,
            'passed' => 0,
            'tests' => [],
            'raw_output' => $output,
        ];

        // Extract test counts (PHPUnit format)
        if (preg_match('/Tests: (\d+)/', $output, $matches)) {
            $data['tests_run'] = (int) $matches[1];
        }
        
        if (preg_match('/Assertions: (\d+)/', $output, $matches)) {
            $data['assertions'] = (int) $matches[1];
        }
        
        if (preg_match('/Failures: (\d+)/', $output, $matches)) {
            $data['failures'] = (int) $matches[1];
        }
        
        if (preg_match('/Errors: (\d+)/', $output, $matches)) {
            $data['errors'] = (int) $matches[1];
        }

        if (preg_match('/Warnings: (\d+)/', $output, $matches)) {
            $data['warnings'] = (int) $matches[1];
        }

        if (preg_match('/Skipped: (\d+)/', $output, $matches)) {
            $data['skipped'] = (int) $matches[1];
        }

        $data['passed'] = $data['tests_run'] - $data['failures'] - $data['errors'];

        // Parse individual test results (testdox format)
        $lines = explode("\n", $output);
        $currentSuite = '';
        
        foreach ($lines as $line) {
            // Detect test suite
            if (preg_match('/^([A-Z][\w\\\\]+)$/', trim($line), $matches)) {
                $currentSuite = $matches[1];
                continue;
            }
            
            // Detect test result
            if (preg_match('/^\s*[✓✗⚠]\s+(.+)$/', $line, $matches)) {
                $status = 'passed';
                if (str_contains($line, '✗')) {
                    $status = 'failed';
                } elseif (str_contains($line, '⚠')) {
                    $status = 'warning';
                }
                
                $data['tests'][] = [
                    'suite' => $currentSuite,
                    'name' => trim($matches[1]),
                    'status' => $status,
                ];
            }
        }

        return $data;
    }

    protected function generateMarkdownReport(array $results, float $duration): string
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $status = $results['failures'] > 0 || $results['errors'] > 0 ? '❌ FAILED' : '✅ PASSED';
        
        $markdown = <<<MD
# Test Execution Report
        
**Status:** {$status}  
**Date:** {$timestamp}  
**Duration:** {$duration} seconds  
**Test Type:** {$this->config['type']}  
**Parallel Execution:** %s

---

## Summary

| Metric | Count |
|--------|-------|
| Tests Run | {$results['tests_run']} |
| Passed | {$results['passed']} |
| Failed | {$results['failures']} |
| Errors | {$results['errors']} |
| Warnings | {$results['warnings']} |
| Skipped | {$results['skipped']} |
| Assertions | {$results['assertions']} |

---

## Test Results by Suite

MD;

        $markdown = sprintf(
            $markdown,
            ($this->config['parallel'] ?? false) ? 'Yes' : 'No'
        );

        // Group tests by suite
        $suites = [];
        foreach ($results['tests'] as $test) {
            $suite = $test['suite'] ?: 'Unknown';
            if (!isset($suites[$suite])) {
                $suites[$suite] = [];
            }
            $suites[$suite][] = $test;
        }

        foreach ($suites as $suite => $tests) {
            $markdown .= "\n### {$suite}\n\n";
            
            foreach ($tests as $test) {
                $icon = match($test['status']) {
                    'passed' => '✅',
                    'failed' => '❌',
                    'warning' => '⚠️',
                    default => '○'
                };
                
                $markdown .= "- {$icon} {$test['name']}\n";
            }
        }

        // Add raw output section
        $markdown .= "\n---\n\n## Raw Output\n\n```\n{$results['raw_output']}\n```\n";

        return $markdown;
    }

    protected function sendNotification(array $results, string $filename): void
    {
        // You can implement notifications here
        // Examples: Slack, Email, Discord, etc.
        
        // Example using Laravel notifications:
        // User::find(1)->notify(new TestSuiteCompleted($results, $filename));
    }
}
```

### 3. Configure Test Suites in phpunit.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Livewire">
            <directory>tests/Livewire</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

### 4. Usage Examples

```bash
# Run all tests
php artisan tests:automate

# Run only unit tests
php artisan tests:automate --type=unit

# Run Livewire tests in parallel
php artisan tests:automate --type=livewire --parallel

# Run specific test classes
php artisan tests:automate --tests=UserTest --tests=ProductTest

# Run synchronously for immediate feedback
php artisan tests:automate --type=unit --sync

# Queue for background execution
php artisan tests:automate --type=all
```

### 5. Create a Test Notification (Optional)

```php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\SlackMessage;

class TestSuiteCompleted extends Notification
{
    use Queueable;

    protected array $results;
    protected string $reportFile;

    public function __construct(array $results, string $reportFile)
    {
        $this->results = $results;
        $this->reportFile = $reportFile;
    }

    public function via($notifiable): array
    {
        return ['slack', 'mail'];
    }

    public function toSlack($notifiable): SlackMessage
    {
        $status = $this->results['failures'] > 0 ? '❌' : '✅';
        $color = $this->results['failures'] > 0 ? 'danger' : 'good';
        
        return (new SlackMessage)
            ->content("Test Suite Completed {$status}")
            ->attachment(function ($attachment) use ($color) {
                $attachment->title('Test Results')
                    ->color($color)
                    ->fields([
                        'Tests Run' => $this->results['tests_run'],
                        'Passed' => $this->results['passed'],
                        'Failed' => $this->results['failures'],
                        'Duration' => $this->results['duration'] ?? 'N/A',
                    ]);
            });
    }
}
```

### 6. Schedule Automated Test Runs

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        // Run all tests nightly
        $schedule->command('tests:automate --type=all --parallel')
            ->dailyAt('02:00')
            ->emailOutputOnFailure('dev-team@example.com');

        // Run unit tests every 4 hours
        $schedule->command('tests:automate --type=unit')
            ->everyFourHours();
    }
}
```

## Best Practices & Considerations

### 1. **Queue Configuration**
- Use a dedicated queue for test jobs: `->onQueue('testing')`
- Increase timeout for large test suites
- Consider using `database` or `redis` queue driver

### 2. **Performance Tips**
```php
// In your Job
public $tries = 1; // Don't retry failed test jobs
public $maxExceptions = 1;
public $timeout = 3600; // Adjust based on suite size
```

### 3. **Parallel Testing Requirements**
- Parallel testing requires the `brianium/paratest` package
- Install: `composer require --dev brianium/paratest`
- Each parallel process needs its own database
- Configure in `phpunit.xml`:

```xml
<php>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
</php>
```

### 4. **Memory Management**
```php
// In your Job
protected function buildTestCommand(): string
{
    $command = 'php -d memory_limit=512M artisan test';
    // ... rest of command building
}
```

### 5. **Error Handling**
```php
public function failed(\Throwable $exception)
{
    \Log::error('Test suite job failed', [
        'exception' => $exception->getMessage(),
        'config' => $this->config,
    ]);
    
    // Notify team of failure
    // User::find(1)->notify(new TestJobFailed($exception));
}
```

### 6. **Security Considerations**
- Don't run tests on production databases
- Validate test type input to prevent command injection
- Restrict access to test reports with proper authentication

## Testing Livewire Components

For Livewire-specific testing, ensure your tests extend the proper base class:

```php
<?php

namespace Tests\Livewire;

use App\Livewire\UserProfile;
use Livewire\Livewire;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    /** @test */
    public function component_can_render()
    {
        Livewire::test(UserProfile::class)
            ->assertStatus(200);
    }

    /** @test */
    public function can_update_user_name()
    {
        Livewire::test(UserProfile::class)
            ->set('name', 'John Doe')
            ->call('save')
            ->assertHasNoErrors()
            ->assertEmitted('user-updated');
    }
}
```

## Alternative: Using PEST

If you're using PEST instead of PHPUnit:

```bash
php artisan test --parallel --processes=4
```

The parsing logic would need slight adjustments for PEST's output format.

```

# Interactive Selection in Laravel Commands

You're absolutely right to call that out - my example did **NOT** demonstrate interactive selection! It only showed command-line flags. However, **interactive selection is definitely possible** and Laravel provides excellent tools for building interactive CLI experiences.

## Documentation Reference
- [Prompting for Input](https://laravel.com/docs/12.x/prompts)
- [Artisan Console - Prompting](https://laravel.com/docs/12.x/artisan#prompting-for-input)

## Updated Command with Interactive Selection

Here's an enhanced version with full interactive capabilities:

```php
<?php

namespace App\Console\Commands;

use App\Jobs\RunTestSuiteJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\spin;

class RunAutomatedTests extends Command
{
    protected $signature = 'tests:automate 
                            {--interactive : Run in interactive mode}
                            {--type=all : Type of tests to run}
                            {--tests=* : Specific test classes to run}
                            {--parallel : Run tests in parallel}
                            {--sync : Run synchronously}';

    protected $description = 'Automate test execution with optional interactive selection';

    public function handle()
    {
        // Check if interactive mode is requested or no options provided
        if ($this->option('interactive') || $this->isInteractiveModeNeeded()) {
            return $this->handleInteractiveMode();
        }

        return $this->handleNonInteractiveMode();
    }

    protected function isInteractiveModeNeeded(): bool
    {
        // If no meaningful options provided, go interactive
        return $this->option('type') === 'all' 
            && empty($this->option('tests'))
            && !$this->option('parallel')
            && !$this->option('sync');
    }

    protected function handleInteractiveMode()
    {
        info('🎯 Interactive Test Suite Configuration');
        $this->newLine();

        // Step 1: Select test type
        $testType = select(
            label: 'Which tests would you like to run?',
            options: [
                'all' => 'All Tests',
                'unit' => 'Unit Tests Only',
                'feature' => 'Feature Tests Only',
                'livewire' => 'Livewire Component Tests Only',
                'custom' => 'Select Specific Test Files',
            ],
            default: 'all'
        );

        // Step 2: If custom, show test file selection
        $specificTests = [];
        if ($testType === 'custom') {
            $specificTests = $this->selectSpecificTests();
            
            if (empty($specificTests)) {
                warning('No tests selected. Exiting...');
                return Command::FAILURE;
            }
        }

        // Step 3: Parallel execution?
        $parallel = confirm(
            label: 'Run tests in parallel?',
            default: true,
            hint: 'Parallel execution is faster but requires paratest package'
        );

        // Step 4: Execution mode
        $sync = !confirm(
            label: 'Queue the test job for background execution?',
            default: true,
            hint: 'If No, tests will run immediately and block this terminal'
        );

        // Step 5: Show configuration summary
        $this->displayConfigurationSummary($testType, $specificTests, $parallel, $sync);

        // Step 6: Confirm execution
        if (!confirm('Proceed with test execution?', default: true)) {
            info('Test execution cancelled.');
            return Command::SUCCESS;
        }

        // Execute tests
        return $this->executeTests([
            'type' => $testType,
            'specific_tests' => $specificTests,
            'parallel' => $parallel,
            'sync' => $sync,
        ]);
    }

    protected function selectSpecificTests(): array
    {
        $testFiles = $this->discoverTestFiles();

        if (empty($testFiles)) {
            warning('No test files found!');
            return [];
        }

        // Group tests by directory for better organization
        $groupedTests = $this->groupTestsByDirectory($testFiles);

        // First, select which test suites to explore
        $selectedSuites = multiselect(
            label: 'Select test suites to choose from',
            options: array_keys($groupedTests),
            hint: 'Use space to select, enter to continue'
        );

        if (empty($selectedSuites)) {
            return [];
        }

        // Now select specific tests from chosen suites
        $availableTests = [];
        foreach ($selectedSuites as $suite) {
            $availableTests = array_merge($availableTests, $groupedTests[$suite]);
        }

        $selectedTests = multiselect(
            label: 'Select specific test files to run',
            options: $availableTests,
            hint: 'Use space to select, enter to continue',
            scroll: 15
        );

        return $selectedTests;
    }

    protected function discoverTestFiles(): array
    {
        $testFiles = [];
        $testDirectory = base_path('tests');

        if (!File::isDirectory($testDirectory)) {
            return $testFiles;
        }

        $files = File::allFiles($testDirectory);

        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $relativePath = str_replace($testDirectory . '/', '', $file->getPathname());
                $testFiles[$relativePath] = $this->getTestClassName($file->getPathname());
            }
        }

        return $testFiles;
    }

    protected function getTestClassName(string $filepath): string
    {
        $content = File::get($filepath);
        
        // Extract namespace
        preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch);
        $namespace = $namespaceMatch[1] ?? 'Tests';
        
        // Extract class name
        preg_match('/class\s+(\w+)/', $content, $classMatch);
        $className = $classMatch[1] ?? basename($filepath, '.php');
        
        return $namespace . '\\' . $className;
    }

    protected function groupTestsByDirectory(array $testFiles): array
    {
        $grouped = [];

        foreach ($testFiles as $path => $className) {
            $directory = dirname($path);
            $directory = $directory === '.' ? 'Root' : $directory;
            
            if (!isset($grouped[$directory])) {
                $grouped[$directory] = [];
            }
            
            $grouped[$directory][$path] = $className;
        }

        return $grouped;
    }

    protected function displayConfigurationSummary(
        string $testType, 
        array $specificTests, 
        bool $parallel, 
        bool $sync
    ): void {
        $this->newLine();
        info('📋 Test Configuration Summary');
        $this->table(
            ['Setting', 'Value'],
            [
                ['Test Type', ucfirst($testType)],
                ['Specific Tests', empty($specificTests) ? 'None' : count($specificTests) . ' selected'],
                ['Parallel Execution', $parallel ? 'Yes' : 'No'],
                ['Execution Mode', $sync ? 'Synchronous (Immediate)' : 'Queued (Background)'],
            ]
        );

        if (!empty($specificTests)) {
            $this->newLine();
            info('Selected Test Files:');
            foreach (array_slice($specificTests, 0, 10) as $test) {
                $this->line('  • ' . $test);
            }
            if (count($specificTests) > 10) {
                $this->line('  ... and ' . (count($specificTests) - 10) . ' more');
            }
        }

        $this->newLine();
    }

    protected function handleNonInteractiveMode()
    {
        $config = [
            'type' => $this->option('type'),
            'specific_tests' => $this->option('tests'),
            'parallel' => $this->option('parallel'),
            'sync' => $this->option('sync'),
        ];

        $this->info('🚀 Starting automated test suite...');
        $this->info("Test type: {$config['type']}");

        return $this->executeTests($config);
    }

    protected function executeTests(array $config): int
    {
        $job = new RunTestSuiteJob(array_merge($config, [
            'requested_by' => 'console',
            'requested_at' => now(),
        ]));

        if ($config['sync']) {
            // Run synchronously with progress indicator
            spin(
                callback: fn () => $job->handle(),
                message: 'Running tests...'
            );
            
            $this->newLine();
            info('✅ Tests completed! Check storage/test-reports/ for results.');
            
            // Display quick summary if available
            $this->displayQuickSummary();
        } else {
            dispatch($job);
            info('✅ Test job dispatched to queue!');
            $this->line('   Monitor progress with: php artisan queue:work');
        }

        return Command::SUCCESS;
    }

    protected function displayQuickSummary(): void
    {
        // Get the most recent report
        $reportPath = storage_path('app/test-reports');
        
        if (!File::isDirectory($reportPath)) {
            return;
        }

        $files = File::files($reportPath);
        
        if (empty($files)) {
            return;
        }

        // Get the most recent file
        usort($files, fn($a, $b) => $b->getMTime() <=> $a->getMTime());
        $latestReport = File::get($files[0]->getPathname());

        // Extract summary from markdown
        if (preg_match('/\*\*Status:\*\* (.+)/', $latestReport, $match)) {
            $status = trim($match[1]);
            $this->newLine();
            
            if (str_contains($status, '✅')) {
                $this->components->info('All tests passed!');
            } else {
                $this->components->error('Some tests failed!');
            }
        }

        // Extract metrics
        if (preg_match('/\| Tests Run \| (\d+) \|/', $latestReport, $match)) {
            $testsRun = $match[1];
            $this->line("   Tests run: {$testsRun}");
        }

        if (preg_match('/\| Passed \| (\d+) \|/', $latestReport, $match)) {
            $passed = $match[1];
            $this->line("   Passed: {$passed}");
        }

        if (preg_match('/\| Failed \| (\d+) \|/', $latestReport, $match)) {
            $failed = $match[1];
            if ($failed > 0) {
                $this->line("   <fg=red>Failed: {$failed}</>");
            }
        }

        $this->newLine();
        $this->line("   Full report: {$files[0]->getFilename()}");
    }
}
```

## Enhanced Interactive Features

### 1. **Multi-Step Selection Menu**

Here's an even more advanced version with a wizard-style approach:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

class AdvancedTestRunner extends Command
{
    protected $signature = 'tests:wizard';
    protected $description = 'Wizard-style test runner with advanced selection';

    public function handle()
    {
        $this->displayWelcome();

        $config = [];

        // Step 1: Test scope
        $config['scope'] = $this->selectTestScope();

        // Step 2: Test selection (conditional)
        if ($config['scope'] === 'custom') {
            $config['tests'] = $this->selectTestsWithSearch();
        }

        // Step 3: Execution options
        $config['options'] = $this->configureExecutionOptions();

        // Step 4: Advanced settings
        if (confirm('Configure advanced settings?', false)) {
            $config['advanced'] = $this->configureAdvancedSettings();
        }

        // Execute
        $this->executeWithProgress($config);

        return Command::SUCCESS;
    }

    protected function displayWelcome(): void
    {
        $this->newLine();
        $this->line('╔═══════════════════════════════════════╗');
        $this->line('║   🧪 Test Suite Execution Wizard 🧪   ║');
        $this->line('╚═══════════════════════════════════════╝');
        $this->newLine();
    }

    protected function selectTestScope(): string
    {
        return select(
            label: 'What would you like to test?',
            options: [
                'all' => '🌍 Everything - Run the full test suite',
                'unit' => '⚙️  Unit Tests - Test individual components',
                'feature' => '🎭 Feature Tests - Test user workflows',
                'livewire' => '⚡ Livewire - Test reactive components',
                'custom' => '🎯 Custom - Pick specific tests',
            ],
        );
    }

    protected function selectTestsWithSearch(): array
    {
        $allTests = $this->getAllTestClasses();

        // Use search for finding tests
        $selectedTests = [];
        $continueAdding = true;

        while ($continueAdding) {
            $test = search(
                label: 'Search for a test to add',
                options: fn (string $value) => strlen($value) > 0
                    ? array_filter(
                        $allTests,
                        fn ($test) => str_contains(strtolower($test), strtolower($value))
                    )
                    : [],
                placeholder: 'Type to search...',
                hint: 'Start typing a test name'
            );

            if ($test) {
                $selectedTests[] = $test;
                info("✓ Added: {$test}");
            }

            $continueAdding = confirm(
                label: 'Add another test?',
                default: false
            );
        }

        return $selectedTests;
    }

    protected function configureExecutionOptions(): array
    {
        $options = [];

        $options['parallel'] = select(
            label: 'Execution mode',
            options: [
                'parallel' => '⚡ Parallel - Faster execution',
                'sequential' => '📝 Sequential - Easier debugging',
            ],
        ) === 'parallel';

        $options['stop_on_failure'] = confirm(
            label: 'Stop on first failure?',
            default: false,
            hint: 'Useful for debugging'
        );

        $options['coverage'] = confirm(
            label: 'Generate code coverage report?',
            default: false,
            hint: 'This will slow down test execution'
        );

        return $options;
    }

    protected function configureAdvancedSettings(): array
    {
        return [
            'memory_limit' => text(
                label: 'Memory limit',
                default: '512M',
                hint: 'e.g., 512M, 1G'
            ),
            'timeout' => text(
                label: 'Timeout (seconds)',
                default: '300',
                validate: fn ($value) => is_numeric($value) 
                    ? null 
                    : 'Must be a number'
            ),
        ];
    }

    protected function executeWithProgress(array $config): void
    {
        info('Starting test execution...');
        $this->newLine();

        // Simulate progress (in real implementation, this would track actual test progress)
        $steps = [
            'Preparing test environment',
            'Loading test files',
            'Running tests',
            'Generating reports',
            'Cleanup',
        ];

        progress(
            label: 'Test Execution Progress',
            steps: $steps,
            callback: function ($step, $progress) {
                sleep(1); // Simulate work
                // In real implementation, you'd actually execute each step
            }
        );

        $this->newLine();
        $this->components->info('Test execution completed!');
    }

    protected function getAllTestClasses(): array
    {
        // Same implementation as before
        return [];
    }
}
```

## Real-Time Test Progress Monitoring

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use function Laravel\Prompts\progress;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\note;

class InteractiveTestRunner extends Command
{
    protected $signature = 'tests:interactive';
    
    public function handle()
    {
        $config = $this->buildInteractiveConfig();
        
        // Run tests with real-time output
        $this->runTestsWithLiveOutput($config);
    }

    protected function runTestsWithLiveOutput(array $config): void
    {
        $command = $this->buildTestCommand($config);
        
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(3600);
        
        $this->info('🚀 Executing tests...');
        $this->newLine();
        
        $testResults = [];
        $currentTest = null;
        
        $process->run(function ($type, $buffer) use (&$testResults, &$currentTest) {
            // Parse output in real-time
            $lines = explode("\n", $buffer);
            
            foreach ($lines as $line) {
                // Display test progress
                if (str_contains($line, '✓') || str_contains($line, '✗')) {
                    $this->line($line);
                }
                
                // Track individual test results
                if (preg_match('/✓\s+(.+)/', $line, $matches)) {
                    $testResults[] = ['name' => $matches[1], 'status' => 'passed'];
                } elseif (preg_match('/✗\s+(.+)/', $line, $matches)) {
                    $testResults[] = ['name' => $matches[1], 'status' => 'failed'];
                }
            }
        });
        
        $this->newLine();
        $this->displayFinalSummary($testResults, $process->isSuccessful());
    }

    protected function displayFinalSummary(array $results, bool $success): void
    {
        $passed = count(array_filter($results, fn($r) => $r['status'] === 'passed'));
        $failed = count(array_filter($results, fn($r) => $r['status'] === 'failed'));
        
        $summary = "
        ╔═══════════════════════════════════════╗
        ║          TEST EXECUTION SUMMARY        ║
        ╠═══════════════════════════════════════╣
        ║  Total Tests: " . str_pad($passed + $failed, 24) . "║
        ║  ✅ Passed:   " . str_pad($passed, 24) . "║
        ║  ❌ Failed:   " . str_pad($failed, 24) . "║
        ╚═══════════════════════════════════════╝
        ";
        
        if ($success) {
            $this->components->info($summary);
        } else {
            $this->components->error($summary);
        }
    }
}
```

## Usage Examples

```bash
# Interactive mode (prompts for all options)
php artisan tests:automate --interactive

# Auto-detect and go interactive if no options provided
php artisan tests:automate

# Wizard mode with full guided setup
php artisan tests:wizard

# Interactive runner with real-time output
php artisan tests:interactive

# Non-interactive with flags (as before)
php artisan tests:automate --type=unit --parallel
```

## Key Interactive Features

1. **`select()`** - Single choice from list
2. **`multiselect()`** - Multiple choices with spacebar
3. **`search()`** - Searchable dropdown with autocomplete
4. **`confirm()`** - Yes/No questions
5. **`text()`** - Free text input with validation
6. **`progress()`** - Progress bars for long operations
7. **`spin()`** - Loading spinners
8. **`note()`** - Display information boxes

## Best Practices for Interactive CLIs

1. **Provide defaults** for common choices
2. **Show hints** to guide users
3. **Validate input** immediately
4. **Allow both interactive and non-interactive** modes
5. **Display summaries** before execution
6. **Give clear feedback** during execution
7. **Handle interruptions** gracefully (Ctrl+C)

The Laravel Prompts package makes building beautiful, interactive CLI applications incredibly easy! This gives your team a much better UX compared to remembering command flags.
