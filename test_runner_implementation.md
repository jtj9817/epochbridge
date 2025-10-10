# Test Runner Implementation Tasks

## Phase 4: Real-time Markdown Reporting System

### GOAL
Implement a production-ready Markdown report generator that creates GitHub-compatible test reports with atomic real-time updates during parallel test execution.

### CONTEXT
- Laravel 12 application using PestPHP with parallel test execution via `php artisan test --parallel`
- Event-driven architecture with `TestCompleted` and `TestExecutionCompleted` events from Phase 3
- Multi-process concurrent environment requiring thread-safe file operations
- Reports must follow GitHub Flavored Markdown specification for maximum compatibility
- Target output: `test-report.md` (configurable via command option `--output-file`)

### TASK
Create `MarkdownReportService`, `UpdateMarkdownReport` listener, and Markdown template that work together to generate comprehensive, real-time test reports with proper formatting, statistics, error details, and execution metrics while ensuring data integrity during concurrent writes from parallel test processes.

### REQUIREMENTS

#### 1. Service Implementation (`app/Services/MarkdownReportService.php`)
- `initializeReport($outputFile, $totalTests, $startTime)`: Create initial report structure with header, metadata, and empty sections
- `updateTestResult($outputFile, $testFile, $status, $duration, $output, $errors)`: Append individual test results in real-time using file locking
- `generateSummarySection($outputFile, $summary)`: Generate final statistics (pass/fail counts, duration, performance metrics)
- `formatTestOutput($output)`: Escape and format test output as fenced code blocks with proper syntax highlighting
- `formatErrorDetails($errors)`: Format stack traces and error messages with line numbers and file references
- Implement atomic file operations using `flock()` or Laravel's `File::append()` with locking
- Handle concurrent writes from multiple test processes safely

#### 2. Event Listener (`app/Listeners/UpdateMarkdownReport.php`)
- Listen to `TestCompleted` event: call `updateTestResult()` to append test result immediately
- Listen to `TestExecutionCompleted` event: call `generateSummarySection()` to finalize report
- Implement file locking mechanism to prevent race conditions during concurrent writes
- Handle listener failures gracefully with proper error logging

#### 3. Report Template Structure
- **Header Section**: Title, execution timestamp, environment info (PHP version, Laravel version, PestPHP version)
- **Executive Summary**: Total tests, passed, failed, skipped, duration, pass rate percentage
- **Test Results by Type**: Grouped sections for Unit, Feature, Livewire, Browser tests with individual results
- **Failed Tests Section**: Detailed breakdown of failures with error messages, stack traces, and affected files
- **Performance Metrics**: Slowest tests, average execution time, parallel efficiency stats
- **Footer**: Command used, configuration details, completion timestamp

#### 4. Markdown Formatting Requirements
- Use GitHub Flavored Markdown (GFM) syntax
- Proper heading hierarchy (H1 for title, H2 for major sections, H3 for subsections)
- Fenced code blocks with language identifiers: ` ```php `, ` ```bash `, ` ``` `
- Tables for statistics using pipe syntax with proper alignment
- Emoji indicators for status: ✅ passed, ❌ failed, ⏭️ skipped, ⏱️ duration
- Escape special Markdown characters in test output (backticks, asterisks, underscores)
- Proper line breaks and spacing for readability

#### 5. Event Registration (`app/Providers/EventServiceProvider.php`)
- Map `TestCompleted` → `UpdateMarkdownReport::class`
- Map `TestExecutionCompleted` → `UpdateMarkdownReport::class`
- Ensure listener is called synchronously for each event

### GUIDELINES
- Use Laravel's `Storage` facade or `File` facade for all file operations
- Implement `flock(LOCK_EX)` for exclusive write locks to prevent corruption during parallel writes
- Follow Laravel exception handling conventions with try-catch blocks and proper error logging
- Use Laravel's `Log` facade for debugging file operations and concurrent access issues
- Adhere to Laravel service class patterns with dependency injection
- Format all code according to Laravel Pint standards (PSR-12)
- Test with multiple parallel processes to verify thread-safety
- Ensure report remains valid Markdown even if interrupted mid-execution
- Include proper PHPDoc blocks for all public methods
- Handle edge cases: empty test results, missing files, permission errors, disk space issues
- Optimize for minimal I/O operations while maintaining real-time updates
- Use buffered writes when appending multiple small updates

---

## Phase 5: Progress Tracking and User Interface

### GOAL
Build an interactive console interface with real-time progress tracking, live statistics, and estimated time remaining that provides clear visibility into parallel test execution without blocking the main process.

### CONTEXT
- Laravel 12 Artisan command environment with access to Symfony Console components
- Parallel test execution with multiple concurrent processes from Phase 3
- Event-driven architecture with real-time updates via `TestCompleted` and progress events
- Console output must handle terminal size variations (80-200+ columns)
- Non-blocking updates required to maintain responsiveness during long test runs
- Target: Professional CLI experience similar to `composer install` or `npm install` progress displays

### TASK
Implement `ProgressTrackingService`, enhance `InteractiveTestRunnerCommand` with progress display methods, and create `UpdateProgressDisplay` listener to provide users with live progress bars, real-time statistics, completion estimates, and a comprehensive execution summary upon completion.

### REQUIREMENTS

#### 1. Progress Tracking Service (`app/Services/ProgressTrackingService.php`)
- `initializeProgress($totalTests, $testTypes)`: Set up progress tracking with initial state (total, by type, start time)
- `updateProgress($testFile, $status, $duration)`: Record individual test completion and update statistics
- `displayCurrentStatus($output)`: Render current progress bars, completion percentage, running tests list
- `calculateETA($completed, $total, $startTime)`: Calculate estimated time remaining using average test duration
- `getProgressBar($completed, $total, $width)`: Generate ASCII progress bar (e.g., `[████████░░] 80%`)
- `getStatistics()`: Return current stats (total, completed, passed, failed, skipped, avg duration)
- Track progress separately for each test type (Unit, Feature, Livewire, Browser)
- Maintain running average of test execution time for accurate ETA

#### 2. Command Progress Display (`app/Console/Commands/InteractiveTestRunnerCommand.php`)
- `displayProgressInterface()`: Initialize and show progress UI layout with headers
- `handleProgressUpdates()`: Non-blocking loop that polls for progress updates and refreshes display
- `refreshProgressDisplay()`: Clear previous output and redraw progress bars using `\r` and ANSI codes
- `showExecutionSummary()`: Display final results with formatted table of statistics, duration, pass rate
- Use `$this->output->write()` for same-line updates (carriage return `\r`)
- Use `$this->output->writeln()` for new lines and final summary
- Implement console clearing with ANSI escape codes: `\033[2K` (clear line), `\033[F` (move up)
- Handle graceful degradation for terminals without ANSI support

#### 3. Progress Display Listener (`app/Listeners/UpdateProgressDisplay.php`)
- Listen to `TestCompleted` event: update progress tracking and trigger display refresh
- Listen to `TestExecutionStarted` event: initialize progress display
- Listen to `TestExecutionCompleted` event: show final summary
- Cache console output instance to avoid repeated DI resolution
- Implement non-blocking updates using event dispatching

#### 4. Console UI Elements
- **Overall Progress Bar**: Visual representation of total completion (e.g., `[████████░░] 456/500 tests (91%)`)
- **Test Type Breakdown**: Individual progress for Unit, Feature, Livewire, Browser tests
- **Real-time Statistics**: Passed ✅, Failed ❌, Skipped ⏭️, Duration ⏱️
- **Currently Running**: List of active test files (in parallel mode)
- **ETA Display**: "Estimated time remaining: 2m 34s"
- **Execution Summary Table**: Final results with columns for Type, Total, Passed, Failed, Skipped, Duration
- Use color coding: Green for passed, Red for failed, Yellow for skipped (via Symfony OutputFormatter)

#### 5. Event Registration (`app/Providers/EventServiceProvider.php`)
- Map `TestExecutionStarted` → `UpdateProgressDisplay::class`
- Map `TestCompleted` → `UpdateProgressDisplay::class`
- Map `TestExecutionCompleted` → `UpdateProgressDisplay::class`

### GUIDELINES
- Use Symfony Console's `ProgressBar` component for standardized progress display
- Implement `OutputInterface::writeln()` and `write()` for proper console output control
- Use Laravel's `Console\OutputStyle` for formatted tables and sections
- Handle terminal width dynamically using `$this->output->getTerminal()->getWidth()`
- Implement graceful fallback for non-interactive terminals (CI/CD environments)
- Use ANSI color codes via Symfony's `OutputFormatter::escape()` for safe output
- Follow Laravel command conventions with proper method organization
- Test on multiple terminal emulators (bash, zsh, Windows Terminal, iTerm2)
- Ensure output remains readable during rapid updates (max refresh rate: 10Hz)
- Use non-blocking I/O patterns to avoid delays in test execution
- Include proper error handling for display failures (don't crash on console errors)
- Format durations consistently: "2m 34s" for human readability
- Use Laravel's `Str::plural()` for grammatically correct output ("1 test" vs "2 tests")
- Add visual separators between sections using box drawing characters or lines

---

## Phase 6: Error Handling and Recovery

### GOAL
Implement a robust error handling and recovery system with intelligent retry logic, comprehensive diagnostics, and graceful degradation that ensures the test runner remains reliable even when facing test failures, system errors, or unexpected conditions during parallel execution.

### CONTEXT
- Laravel 12 application with parallel test execution prone to various failure modes
- Multiple failure categories: test assertion failures (expected), system errors (timeouts, OOM), configuration issues (missing dependencies)
- Laravel queue system with job failure handling (`failed()` method, retry mechanisms)
- Production environment where tests may fail due to external dependencies (database, network, file system)
- Need to distinguish between: retriable errors (transient), permanent failures (code issues), system failures (infrastructure)
- Error information must be preserved for debugging while providing actionable user guidance

### TASK
Create `TestExecutionException` custom exception, `ErrorRecoveryService` for intelligent error handling and retry logic, and enhance `ExecuteTestSuiteJob` with comprehensive error handling that categorizes failures, attempts recovery when appropriate, provides detailed diagnostics, and ensures graceful shutdown with proper cleanup.

### REQUIREMENTS

#### 1. Custom Exception (`app/Exceptions/TestExecutionException.php`)
- Extend base `Exception` class with additional test-specific context
- Properties: `$testFile`, `$testContext`, `$errorCategory`, `$isRetriable`, `$diagnosticInfo`
- Error categories as constants: `SYSTEM_ERROR`, `TEST_FAILURE`, `CONFIGURATION_ERROR`, `TIMEOUT_ERROR`, `RESOURCE_ERROR`
- `__construct($message, $category, $testFile, $context, $isRetriable, $previous)`: Constructor with full context
- `getDiagnosticInfo()`: Return array of diagnostic data (environment, resources, configuration)
- `isRetriable()`: Boolean indicating if error should trigger retry logic
- `getRecoveryHint()`: Return user-friendly suggestion for resolving the error
- Implement `__toString()` for comprehensive error reporting

#### 2. Error Recovery Service (`app/Services/ErrorRecoveryService.php`)
- `handleTestFailure($testFile, $error, $attempt)`: Main error handling entry point
- `diagnoseError($error, $context)`: Analyze error and categorize (system vs test vs config)
- `attemptRecovery($testFile, $error, $attempt)`: Execute recovery strategy based on error type
- `shouldRetry($error, $attempt, $maxAttempts)`: Determine if retry is appropriate
- `getRetryDelay($attempt)`: Calculate exponential backoff delay (1s, 2s, 4s, 8s)
- `performSystemCheck()`: Verify system resources (memory, disk space, database connection)
- `cleanupTestEnvironment($testFile)`: Reset test environment state (cache, temp files, connections)
- **Recovery Strategies:**
  - Timeout errors: Increase timeout, reduce parallelism
  - Resource errors: Free memory, close connections, cleanup temp files
  - Database errors: Reset connection, retry migration
  - Configuration errors: Validate config, suggest fixes (non-retriable)
  - Test failures: Log for developer review (non-retriable)

#### 3. Enhanced Job Error Handling (`app/Jobs/ExecuteTestSuiteJob.php` updates)
- Wrap `handle()` method logic in comprehensive try-catch block
- Catch specific exceptions: `TestExecutionException`, `ProcessTimedOutException`, `RuntimeException`, `Throwable`
- For retriable errors: dispatch new job with incremented attempt counter and delay
- For permanent errors: dispatch `TestExecutionFailed` event with full diagnostic info
- Implement `failed(Throwable $exception)` method: cleanup resources, log error, notify user
- Add job properties: `$tries = 3`, `$timeout = 300`, `$backoff = [10, 30, 60]`
- Implement `retryUntil()`: return Carbon deadline for retry cutoff (30 minutes)
- Tag jobs for monitoring: `tags()` method returning test file identifiers
- Use `rescue()` helper for graceful fallback on critical operations

#### 4. Error Logging and Diagnostics
- Log all errors with context using Laravel `Log` facade with appropriate levels
- **Debug level**: Successful recoveries, retry attempts
- **Warning level**: Retriable failures, resource warnings
- **Error level**: Permanent failures, system errors
- **Critical level**: Job system failures, data corruption
- Include diagnostic payload: PHP version, memory usage, disk space, loaded extensions, environment
- Use structured logging with array context for easy parsing
- Create error summary in Markdown report with troubleshooting links

#### 5. Graceful Shutdown and Cleanup
- Register shutdown handler: `register_shutdown_function()` for emergency cleanup
- Implement `cleanup()` method: close processes, delete temp files, release locks
- Handle SIGTERM and SIGINT signals for graceful termination
- Ensure partial reports are marked as incomplete with clear indication
- Preserve error state for post-mortem analysis
- Release all file locks and database connections

#### 6. User Communication
- Display clear error messages in console with recovery actions
- Show progress of retry attempts with countdown
- Provide actionable suggestions based on error category
- Include links to relevant documentation or common fixes
- Display final error summary with all failures and suggested fixes

### GUIDELINES
- Follow Laravel exception handling conventions with proper exception hierarchy
- Use Laravel's `rescue()` helper for non-critical operations with fallbacks
- Implement proper logging with context using `Log::error($message, $context)`
- Use Laravel's retry mechanism with exponential backoff: `retry(3, $callback, 1000)`
- Follow PSR-3 logging standards for severity levels
- Implement circuit breaker pattern for external dependency failures
- Use Laravel's `CatchExceptions` middleware pattern for global handling
- Test error scenarios thoroughly: timeouts, OOM, database failures, permission errors
- Never swallow exceptions silently - always log with context
- Distinguish between expected failures (test assertions) and unexpected errors
- Use Laravel's queue monitoring for tracking failed jobs
- Implement proper cleanup in `finally` blocks for resource management
- Follow Laravel's "fail fast" principle for configuration errors
- Provide specific error messages, avoid generic "Something went wrong"
- Use Laravel's `report()` helper for exception reporting to error tracking services
- Consider rate limiting retries to prevent resource exhaustion

---

## Phase 7: Integration and Configuration

### GOAL
Integrate all test runner components into a cohesive system with comprehensive configuration options, proper dependency injection, environment-specific settings, and clear documentation that enables easy customization and extension.

### CONTEXT
- Laravel 12 application with service container, configuration system, and service provider architecture
- Completed components from Phases 1-6: command, services, jobs, events, listeners
- Need for environment-specific configuration (local, testing, CI/CD, production)
- Multiple configuration axes: parallel processes, timeouts, report formats, retry logic, output preferences
- Must integrate seamlessly with existing EpochBridge codebase and testing infrastructure
- Target users: developers running tests locally, CI/CD pipelines, QA team

### TASK
Create comprehensive configuration file `config/test-runner.php`, implement `TestRunnerServiceProvider` for service registration and binding, update application configuration, and document usage with practical examples to complete the integration of all test runner components.

### REQUIREMENTS

#### 1. Configuration File (`config/test-runner.php`)

**Parallel Execution Settings:**
- `parallel.enabled`: Boolean to enable/disable parallel execution (default: true)
- `parallel.processes`: Number of parallel processes (default: 4, max: CPU count)
- `parallel.auto_detect_cores`: Auto-detect optimal process count (default: true)

**Timeout Settings:**
- `timeouts.test`: Individual test timeout in seconds (default: 60)
- `timeouts.suite`: Full suite timeout in seconds (default: 1800)
- `timeouts.job`: Job execution timeout in seconds (default: 300)

**Report Configuration:**
- `report.default_filename`: Default output file (default: 'test-report.md')
- `report.output_directory`: Report storage location (default: 'storage/test-reports')
- `report.auto_timestamp`: Append timestamp to filename (default: false)
- `report.include_sections`: Array of sections to include (summary, results, failures, performance)
- `report.format`: Report format (markdown, html, json) (default: 'markdown')

**Error Handling:**
- `error_handling.max_retries`: Maximum retry attempts (default: 3)
- `error_handling.retry_delay`: Base delay between retries in seconds (default: 10)
- `error_handling.backoff_strategy`: 'exponential' or 'linear' (default: 'exponential')
- `error_handling.continue_on_failure`: Continue suite execution after failure (default: true)

**Progress Display:**
- `progress.enabled`: Show progress bars (default: true)
- `progress.refresh_rate`: Updates per second (default: 10)
- `progress.show_running_tests`: Display currently executing tests (default: true)
- `progress.show_eta`: Display estimated time remaining (default: true)

**Test Discovery:**
- `discovery.paths`: Array of paths to scan for tests (default: ['tests/Unit', 'tests/Feature', 'tests/Browser'])
- `discovery.patterns`: File patterns to match (default: ['*Test.php'])
- `discovery.exclude`: Paths to exclude (default: ['tests/bootstrap.php'])

**Environment Overrides:**
- Support for environment-specific config files: `test-runner.local.php`, `test-runner.ci.php`
- Use `env()` helper for environment variable overrides: `TEST_RUNNER_PROCESSES`, `TEST_RUNNER_TIMEOUT`
- Include detailed comments explaining each option with examples

#### 2. Service Provider (`app/Providers/TestRunnerServiceProvider.php`)
- Extend `Illuminate\Support\ServiceProvider`
- **`register()` method:**
  - Bind `TestDiscoveryService` as singleton
  - Bind `TestSelectionService` as singleton
  - Bind `TestExecutionService` as singleton with config injection
  - Bind `MarkdownReportService` as singleton with config injection
  - Bind `ProgressTrackingService` as singleton
  - Bind `ErrorRecoveryService` as singleton with config injection
  - Register configuration: `$this->mergeConfigFrom(__DIR__.'/../../config/test-runner.php', 'test-runner')`
- **`boot()` method:**
  - Publish configuration: `$this->publishes([...], 'test-runner-config')`
  - Register event listeners if not auto-discovered
  - Register commands: `$this->commands([InteractiveTestRunnerCommand::class])`
  - Validate configuration on boot in non-production environments
- Implement `provides()` method listing provided services for deferred loading
- Add conditional service registration based on environment (skip in production)

#### 3. Application Configuration Updates
- **`config/app.php`:**
  - Add `App\Providers\TestRunnerServiceProvider::class` to `providers` array
  - Ensure provider loads after base Laravel providers
- **`bootstrap/app.php` (Laravel 12):**
  - If using new bootstrap structure, register provider appropriately
- **`.env.example`:**
  - Add test runner environment variables with descriptions:
    ```bash
    # Test Runner Configuration
    TEST_RUNNER_PROCESSES=4
    TEST_RUNNER_TIMEOUT=300
    TEST_RUNNER_OUTPUT_FILE=test-report.md
    TEST_RUNNER_RETRY_ATTEMPTS=3
    ```

#### 4. Dependency Injection Setup
- Update all services to accept configuration array in constructor
- Use constructor injection for service dependencies (avoid facades in services)
- **Example pattern:**
  ```php
  public function __construct(
      private array $config,
      private TestDiscoveryService $discovery,
      private Filesystem $files
  ) {
      $this->config = $config;
  }
  ```
- Ensure all services use interface contracts where appropriate (future-proofing)
- Type-hint all dependencies for better IDE support and runtime validation

#### 5. README Documentation Update (`README.md`)
- Add "Test Runner" section under existing Testing documentation
- **Subsections:**
  - **Overview**: Brief description of interactive test runner capabilities
  - **Basic Usage:**
    ```bash
    php artisan test:runner
    # or with custom output file
    php artisan test:runner --output-file=my-report.md
    ```
  - **Interactive Menus**: Screenshot or text illustration of menu options
  - **Configuration**: Link to `config/test-runner.php` with key options
  - **Output Format**: Sample Markdown report structure
  - **CI/CD Integration**: Example GitHub Actions / GitLab CI configuration
  - **Troubleshooting**: Common issues and solutions
- Include practical examples for different use cases:
  - Running specific test types (Unit only)
  - Custom test selection
  - Parallel execution tuning
  - Error recovery scenarios

#### 6. Additional Documentation
- Inline PHPDoc blocks for all configuration keys
- Command help text: `php artisan test:runner --help`
- Configuration validation messages for invalid settings
- Example configuration files for common scenarios (CI, local dev, staging)

### GUIDELINES
- Follow Laravel configuration conventions with array structure and type safety
- Use sensible defaults that work for 80% of use cases without configuration
- Implement configuration validation with clear error messages for invalid values
- Support environment variable overrides using `env()` helper for all critical settings
- Follow Laravel service provider patterns with proper `register()` and `boot()` separation
- Use deferred service providers (`DeferrableProvider` interface) for performance
- Implement proper dependency injection avoiding use of facades within services
- Follow PSR-4 autoloading standards for all classes
- Add configuration caching support: `php artisan config:cache` should work correctly
- Test configuration in multiple environments (local, testing, CI/CD)
- Provide migration path for future breaking changes
- Use semantic versioning for any published packages
- Include type declarations for all configuration values (PHP 8.2+ features)
- Document performance implications of configuration choices
- Consider backward compatibility when adding new configuration options

---

## Phase 8: Testing and Documentation

### GOAL
Achieve comprehensive test coverage for the test runner system using meta-testing approaches, create both user-facing and developer documentation, validate reliability across different scenarios, and ensure the system is production-ready with proper error handling verification.

### CONTEXT
- Laravel 12 application using PestPHP for testing framework
- Meta-testing challenge: testing a system that runs tests requires careful isolation and mocking
- Existing test structure: `tests/Unit/`, `tests/Feature/`, `tests/Browser/`
- Target coverage: >90% for critical paths (services, command logic, error handling)
- Documentation audience: developers (contributors), users (test runners), operations (CI/CD)
- Need to verify: parallel execution correctness, report generation accuracy, error recovery reliability, console output quality

### TASK
Implement comprehensive unit tests for all service classes, feature tests for command interactions and workflows, integration tests for end-to-end scenarios, and create user guide and developer documentation that cover usage, architecture, extension points, and troubleshooting.

### REQUIREMENTS

#### 1. Unit Tests for Services

**`tests/Unit/Services/TestDiscoveryServiceTest.php`:**
- Mock filesystem using `Storage::fake()` and virtual file structure
- Test `discoverAllTests()`: verify all test files found in various directory structures
- Test `getUnitTests()`, `getFeatureTests()`, `getLivewireTests()`, `getBrowserTests()`: verify correct categorization
- Test `categorizeTestFile()`: verify regex patterns match correctly for different naming conventions
- Test `validateTestFile()`: verify valid tests pass, invalid files rejected
- Edge cases: empty directories, deeply nested structures, non-test PHP files, symlinks
- Performance test: discovery on large test suites (1000+ files)

**`tests/Unit/Services/MarkdownReportServiceTest.php`:**
- Mock file system and use temporary files for testing
- Test `initializeReport()`: verify report structure, headers, metadata
- Test `updateTestResult()`: verify proper formatting, escaping, status indicators
- Test `generateSummarySection()`: verify statistics accuracy, formatting, calculations
- Test `formatTestOutput()`: verify proper Markdown escaping, code block formatting
- Test `formatErrorDetails()`: verify stack trace formatting, file references, line numbers
- Concurrency test: simulate multiple concurrent writes, verify no corruption (use actual file locking)
- Edge cases: empty output, very large output, special characters, Unicode
- Validate generated Markdown against GFM specification

**`tests/Unit/Services/ErrorRecoveryServiceTest.php`:**
- Mock external dependencies (database, filesystem, processes)
- Test `diagnoseError()`: verify correct error categorization for various exception types
- Test `shouldRetry()`: verify retry logic for different error categories and attempt counts
- Test `getRetryDelay()`: verify exponential backoff calculations
- Test `performSystemCheck()`: mock system resource checks
- Test `cleanupTestEnvironment()`: verify proper cleanup of resources
- Test recovery strategies for each error type
- Edge cases: max retries exceeded, system resource exhaustion, configuration errors

**`tests/Unit/Services/ProgressTrackingServiceTest.php`:**
- Test `initializeProgress()`: verify initial state setup
- Test `updateProgress()`: verify statistics tracking accuracy over multiple updates
- Test `calculateETA()`: verify ETA calculations with various completion rates
- Test `getProgressBar()`: verify ASCII progress bar rendering at different widths
- Edge cases: zero tests, single test, completion before ETA calculation possible

#### 2. Feature Tests for Command Interactions

**`tests/Feature/Console/InteractiveTestRunnerCommandTest.php`:**
- Use `$this->artisan('test:runner')` for command testing
- Test main menu display: verify all options shown, correct order
- Test test type selection: simulate user choosing Unit, Feature, All tests
- Test custom test selection: mock interactive file selection
- Test `--output-file` option: verify custom filename used
- Test validation: invalid selections, empty selections, non-existent paths
- Test command output: verify progress bars shown, summary displayed
- Mock `TestDiscoveryService` to avoid actual filesystem dependency
- Mock `TestExecutionService` to avoid running actual tests
- Verify events dispatched correctly: `TestExecutionStarted`, `TestCompleted`, `TestExecutionCompleted`
- Test error handling: service failures, user cancellation (SIGINT)
- Assert exit codes: 0 for success, 1 for failures

#### 3. Feature Tests for Jobs

**`tests/Feature/Jobs/ExecuteTestSuiteJobTest.php`:**
- Use `Queue::fake()` for job testing without actual queue execution
- Test job dispatching: verify correct properties passed
- Test job execution: mock process execution, verify command construction
- Test progress updates: verify events dispatched during execution
- Test parallel coordination: simulate multiple jobs running concurrently
- Test timeout handling: simulate long-running test, verify timeout respected
- Test error handling: simulate various failures, verify `failed()` method called
- Test retry logic: verify retry attempts with proper delays
- Use `Bus::fake()` and `Bus::assertDispatched()` for job assertions
- Mock Symfony Process component to avoid running actual tests
- Verify Markdown report updates during execution

#### 4. Integration Tests

**`tests/Feature/Integration/FullTestRunnerWorkflowTest.php`:**
- End-to-end test: run command → discover tests → execute → generate report
- Use small subset of actual tests from project (2-3 simple tests)
- Verify report file created with correct content
- Verify all events fired in correct order
- Verify progress tracking works end-to-end
- Test with different test type selections
- Test error recovery: include intentionally failing test, verify retry logic
- Verify cleanup: no leftover processes, temp files removed
- Performance test: measure execution time overhead of test runner vs direct `php artisan test`

#### 5. User Documentation

**`docs/test-runner-user-guide.md`:**
- **Introduction**: Purpose, benefits, when to use test runner vs standard `php artisan test`
- **Installation**: Steps to set up (included by default in EpochBridge)
- **Getting Started**: Basic usage with screenshots/ASCII recordings
- **Command Reference:**
  - `php artisan test:runner` - Interactive mode
  - `--output-file=FILE` - Custom report location
  - Future options (if planned)
- **Interactive Menus**: Detailed explanation of each menu option with use cases
- **Understanding Reports:**
  - Report structure explanation
  - Reading statistics (pass rate, duration)
  - Interpreting failed test details
  - Performance metrics explanation
- **Configuration**: How to customize via `config/test-runner.php`
- **CI/CD Integration:**
  - GitHub Actions example workflow
  - GitLab CI example configuration
  - Jenkins pipeline example
  - Handling reports in CI (artifacts, parsing)
- **Troubleshooting:**
  - Common issues: timeouts, OOM, permission errors, parallel execution failures
  - Solutions and workarounds for each
  - Debug mode instructions
  - Log file locations
- **FAQ**: Frequently asked questions with practical answers
- **Examples**: Real-world usage scenarios with step-by-step instructions

#### 6. Developer Documentation

**`docs/test-runner-developer-guide.md`:**
- **Architecture Overview:**
  - Component diagram showing services, jobs, events, listeners
  - Data flow diagram for test execution
  - Sequence diagrams for key workflows
- **Service Descriptions:**
  - Purpose, responsibilities, and public API for each service
  - Dependency map showing service relationships
- **Extension Points:**
  - Adding new test types (e.g., integration tests category)
  - Custom report formats (HTML, JSON)
  - Additional event listeners
  - Custom error recovery strategies
- **Configuration Deep Dive:**
  - All configuration options explained in detail
  - Environment-specific configuration strategies
  - Performance tuning guidelines
- **Testing the Test Runner:**
  - Meta-testing strategies
  - Mocking approaches
  - Integration test patterns
- **Contributing Guidelines:**
  - Code style requirements (Laravel Pint)
  - PR process and expectations
  - Test coverage requirements
  - Documentation updates required for changes
- **API Reference**: Generated PHPDoc documentation for all public methods
- **Performance Considerations:**
  - Parallel execution overhead
  - Report generation performance
  - Memory usage patterns
  - Optimization strategies

### GUIDELINES
- Use PestPHP syntax throughout: `test()`, `it()`, `expect()`, `toBe()`
- Follow existing test patterns in EpochBridge codebase for consistency
- Use `Pest::faker()` for generating realistic test data
- Implement proper test isolation: each test should be independent
- Use test helpers: `actingAs()`, `withoutExceptionHandling()`, `withoutEvents()`
- Mock external dependencies consistently using Laravel's testing utilities
- Use factories for creating test data (User, SavedConversion models)
- Follow AAA pattern: Arrange, Act, Assert in all tests
- Use descriptive test names: `it('categorizes feature tests correctly')`
- Group related tests using `describe()` for better organization
- Use `beforeEach()` and `afterEach()` for setup/teardown
- Test both happy paths and edge cases for each method
- Verify not just success, but correct behavior and side effects
- Use snapshot testing for complex outputs (Markdown reports)
- Include performance benchmarks for critical paths (discovery, report generation)
- Write clear, concise documentation with practical examples
- Use diagrams where helpful (Mermaid format for GitHub compatibility)
- Include code examples in documentation with syntax highlighting
- Follow GFM (GitHub Flavored Markdown) for all documentation
- Create table of contents for long documents
- Use proper heading hierarchy (H1 for title, H2 for major sections)
- Include timestamps and version information in documentation
- Review documentation for clarity and completeness before finalizing
- Test all documentation code examples to ensure they work
