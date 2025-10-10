# Test Runner Implementation Plan

## Overview

This document outlines the implementation plan for creating an interactive Laravel command and job system that provides parallel test execution with real-time Markdown reporting for the EpochBridge calendar conversion platform.

## Phase 1: Core Command Structure

**GOAL:** Create the foundational Laravel Artisan command with interactive menu system

**CONTEXT:** Laravel 12 application using PestPHP for testing with existing test structure in `tests/` directory organized into Unit, Feature, and Browser subdirectories

**TASK:** Implement the base command class with interactive selection capabilities

**REQUIREMENTS:**
- Create Laravel Artisan command with signature `test:runner`
- Implement interactive menu system using Laravel's built-in console components
- Add validation for user selections
- Establish command options and arguments

**GUIDELINES:**
- Use Laravel's `Illuminate\Console\Command` base class
- Utilize `choice()` method for menu interactions
- Follow Laravel naming conventions for commands
- Include proper command description and help text

### Files to Create/Modify:

#### 1. `app/Console/Commands/InteractiveTestRunnerCommand.php`
- **Purpose:** Main command class handling user interaction
- **Implementation:**
  - Extend `Illuminate\Console\Command`
  - Define signature: `test:runner {--output-file=test-report.md}`
  - Implement `handle()` method with menu system
  - Create methods: `showMainMenu()`, `getTestSelection()`, `validateSelection()`
  - Add test type constants: UNIT_TESTS, FEATURE_TESTS, LIVEWIRE_TESTS, BROWSER_TESTS, CUSTOM_SELECTION, ALL_TESTS

#### 2. `app/Console/Kernel.php`
- **Purpose:** Register the new command
- **Implementation:**
  - Add `InteractiveTestRunnerCommand::class` to `$commands` array

## Phase 2: Test Discovery and Selection System

**GOAL:** Implement comprehensive test file discovery and selection mechanisms

**CONTEXT:** PestPHP test structure with specific directories for different test types including calendar service tests and Livewire component tests

**TASK:** Create test discovery service to locate and categorize test files

**REQUIREMENTS:**
- Scan test directories and categorize test files by type
- Implement custom test selection with file browser interface
- Handle edge cases like empty directories or invalid selections
- Support glob patterns for flexible test selection

**GUIDELINES:**
- Use Laravel's File facade for filesystem operations
- Implement robust error handling for file operations
- Follow Single Responsibility Principle for service classes
- Cache discovered tests for performance

### Files to Create/Modify:

#### 1. `app/Services/TestDiscoveryService.php`
- **Purpose:** Handle test file discovery and categorization
- **Implementation:**
  - Create methods: `discoverAllTests()`, `getUnitTests()`, `getFeatureTests()`, `getLivewireTests()`, `getBrowserTests()`
  - Implement `scanDirectory()` with recursive file discovery
  - Add `categorizeTestFile()` method using file path analysis
  - Include `validateTestFile()` to ensure files are executable tests

#### 2. `app/Services/TestSelectionService.php`
- **Purpose:** Handle custom test selection interface
- **Implementation:**
  - Create `displayFileTree()` method for hierarchical test display
  - Implement `selectCustomTests()` with checkbox-style selection
  - Add `validateCustomSelection()` for user input validation
  - Include `buildTestCommand()` to construct PestPHP command arguments

#### 3. Update `app/Console/Commands/InteractiveTestRunnerCommand.php`
- **Modifications:**
  - Inject `TestDiscoveryService` and `TestSelectionService` in constructor
  - Add `selectCustomTests()` method calling selection service
  - Implement `displayTestSummary()` showing selected tests count and types

## Phase 3: Parallel Test Execution System

**GOAL:** Implement parallel test execution using PestPHP with progress tracking

**CONTEXT:** Laravel application with queue system enabled, PestPHP configured with parallel execution support

**TASK:** Create job system for parallel test execution with real-time progress updates

**REQUIREMENTS:**
- Execute tests using `php artisan test --parallel --processes=4`
- Implement progress tracking and status updates
- Handle test output parsing and result extraction
- Support job cancellation and error recovery

**GUIDELINES:**
- Use Laravel's job queue system for background processing
- Implement proper error handling and logging
- Follow Laravel job conventions and best practices
- Ensure thread-safe operations for parallel execution

### Files to Create/Modify:

#### 1. `app/Jobs/ExecuteTestSuiteJob.php`
- **Purpose:** Handle parallel test execution in background
- **Implementation:**
  - Extend `Illuminate\Contracts\Queue\ShouldQueue`
  - Implement `handle()` method with test execution logic
  - Add properties: `$testFiles`, `$outputFile`, `$commandId`
  - Include `executeTestCommand()` using Symfony Process component
  - Add `parseTestOutput()` for result extraction

#### 2. `app/Services/TestExecutionService.php`
- **Purpose:** Coordinate test execution and progress tracking
- **Implementation:**
  - Create `startTestExecution()` method dispatching jobs
  - Implement `trackProgress()` with real-time updates
  - Add `handleTestCompletion()` for individual test results
  - Include `generateExecutionSummary()` for final reporting

#### 3. `app/Events/TestExecutionStarted.php`
- **Purpose:** Event for test execution initiation
- **Implementation:**
  - Extend `Illuminate\Foundation\Events\Dispatchable`
  - Include properties: `$commandId`, `$testCount`, `$startTime`

#### 4. `app/Events/TestCompleted.php`
- **Purpose:** Event for individual test completion
- **Implementation:**
  - Include properties: `$testFile`, `$status`, `$duration`, `$output`, `$errors`

#### 5. `app/Events/TestExecutionCompleted.php`
- **Purpose:** Event for complete test suite completion
- **Implementation:**
  - Include properties: `$commandId`, `$summary`, `$endTime`

## Phase 4: Real-time Markdown Reporting System

**GOAL:** Create comprehensive Markdown report generation with real-time updates

**CONTEXT:** GitHub-compatible Markdown format with proper formatting for test results, execution times, and error details

**TASK:** Implement Markdown report generator with live updates during test execution

**REQUIREMENTS:**
- Generate GitHub-compatible Markdown with proper formatting
- Update report in real-time as tests complete
- Include test statistics, execution times, and error details
- Support report sections for different test types

**GUIDELINES:**
- Use atomic file operations to prevent corruption during concurrent writes
- Follow Markdown best practices for readability
- Include proper escaping for special characters in test output
- Implement report templating for consistent formatting

### Files to Create/Modify:

#### 1. `app/Services/MarkdownReportService.php`
- **Purpose:** Generate and manage Markdown test reports
- **Implementation:**
  - Create `initializeReport()` method setting up report structure
  - Implement `updateTestResult()` for individual test updates
  - Add `generateSummarySection()` with statistics and metrics
  - Include `formatTestOutput()` with proper Markdown escaping
  - Add `formatErrorDetails()` with code block formatting

#### 2. `app/Listeners/UpdateMarkdownReport.php`
- **Purpose:** Listen to test events and update report
- **Implementation:**
  - Listen to `TestCompleted` and `TestExecutionCompleted` events
  - Implement `handle()` method calling report service
  - Add file locking mechanism for concurrent write safety

#### 3. `resources/views/test-report-template.md`
- **Purpose:** Markdown template for consistent report formatting
- **Implementation:**
  - Define report structure with placeholders
  - Include sections for: Executive Summary, Test Results by Type, Failed Tests, Performance Metrics
  - Add timestamp and execution environment information

#### 4. Update `app/Providers/EventServiceProvider.php`
- **Modifications:**
  - Register event listeners in `$listen` array
  - Map `TestCompleted` and `TestExecutionCompleted` to `UpdateMarkdownReport`

## Phase 5: Progress Tracking and User Interface

**GOAL:** Implement real-time progress display and user feedback during test execution

**CONTEXT:** Console-based interface with live updates showing test progress, completion status, and estimated time remaining

**TASK:** Create progress tracking system with console output management

**REQUIREMENTS:**
- Display real-time progress bars and status updates
- Show test completion rates and estimated time remaining
- Handle console output buffering and clearing
- Provide user controls for monitoring execution

**GUIDELINES:**
- Use Laravel's console output components effectively
- Implement non-blocking progress updates
- Follow console UI best practices for readability
- Handle terminal size variations gracefully

### Files to Create/Modify:

#### 1. `app/Services/ProgressTrackingService.php`
- **Purpose:** Manage progress tracking and console display
- **Implementation:**
  - Create `initializeProgress()` method setting up progress bars
  - Implement `updateProgress()` with test completion tracking
  - Add `displayCurrentStatus()` showing running tests and completion rate
  - Include `calculateETA()` for time estimation

#### 2. `app/Console/Commands/InteractiveTestRunnerCommand.php` (Additional Updates)
- **Modifications:**
  - Add `displayProgressInterface()` method
  - Implement `handleProgressUpdates()` with non-blocking console updates
  - Add `showExecutionSummary()` displaying final results

#### 3. `app/Listeners/UpdateProgressDisplay.php`
- **Purpose:** Update console progress display on test events
- **Implementation:**
  - Listen to test execution events
  - Update progress bars and status displays
  - Handle console clearing and redrawing

## Phase 6: Error Handling and Recovery

**GOAL:** Implement comprehensive error handling and recovery mechanisms

**CONTEXT:** Robust system handling test failures, system errors, and unexpected conditions during parallel execution

**TASK:** Create error handling system with graceful degradation and recovery options

**REQUIREMENTS:**
- Handle test execution failures and system errors
- Implement retry mechanisms for failed tests
- Provide error logging and diagnostic information
- Support graceful shutdown and cleanup

**GUIDELINES:**
- Follow Laravel exception handling conventions
- Implement proper logging for debugging
- Use try-catch blocks appropriately
- Provide helpful error messages to users

### Files to Create/Modify:

#### 1. `app/Exceptions/TestExecutionException.php`
- **Purpose:** Custom exception for test execution errors
- **Implementation:**
  - Extend base Exception class
  - Include test context and diagnostic information
  - Add error categorization (system, test, configuration)

✅ Added `TestExecutionException` with category metadata and contextual payload support for downstream diagnostics.

#### 2. `app/Services/ErrorRecoveryService.php`
- **Purpose:** Handle error recovery and retry logic
- **Implementation:**
  - Create `handleTestFailure()` method with retry logic
  - Implement `diagnoseError()` for error analysis
  - Add `attemptRecovery()` with fallback strategies

✅ Implemented `ErrorRecoveryService` to diagnose failures, orchestrate exponential backoff, and surface retry guidance per category.

#### 3. Update `app/Jobs/ExecuteTestSuiteJob.php`
- **Modifications:**
  - Add comprehensive error handling in `handle()` method
  - Implement `failed()` method for job failure handling
  - Add retry logic with exponential backoff

✅ `ExecuteTestSuiteJob` now routes failures through the recovery service, retries errored tests with exponential backoff, enriches emitted metadata, and escalates diagnostics from the `failed()` hook.

## Phase 7: Integration and Configuration

**GOAL:** Integrate all components and provide configuration options

**CONTEXT:** Complete system integration with configurable options for different environments and use cases

**TASK:** Finalize integration and add configuration management

**REQUIREMENTS:**
- Integrate all services and components seamlessly
- Add configuration options for customization
- Implement environment-specific settings
- Provide documentation and usage examples

**GUIDELINES:**
- Use Laravel's configuration system effectively
- Follow Laravel service provider patterns
- Implement proper dependency injection
- Provide clear documentation and examples

### Files to Create/Modify:

#### 1. `config/test-runner.php`
- **Purpose:** Configuration file for test runner settings
- **Implementation:**
  - Define default settings: parallel processes, timeout values, report formats
  - Include environment-specific configurations
  - Add customizable paths and file patterns

#### 2. `app/Providers/TestRunnerServiceProvider.php`
- **Purpose:** Service provider for test runner services
- **Implementation:**
  - Register all test runner services in container
  - Bind interfaces to implementations
  - Configure event listeners and job handlers

#### 3. Update `config/app.php`
- **Modifications:**
  - Add `TestRunnerServiceProvider` to providers array

#### 4. `README.md` (Update)
- **Modifications:**
  - Add section documenting test runner usage
  - Include command examples and configuration options
  - Document report output and interpretation

## Phase 8: Testing and Documentation

**GOAL:** Create comprehensive tests for the test runner system and complete documentation

**CONTEXT:** Meta-testing approach ensuring the test runner itself is thoroughly tested using PestPHP

**TASK:** Implement tests for all test runner components and create user documentation

**REQUIREMENTS:**
- Unit tests for all service classes
- Feature tests for command interactions
- Integration tests for complete workflows
- Comprehensive user and developer documentation

**GUIDELINES:**
- Follow existing testing patterns in the codebase
- Use PestPHP conventions and best practices
- Create realistic test scenarios and edge cases
- Provide clear documentation with examples

### Files to Create/Modify:

#### 1. `tests/Unit/Services/TestDiscoveryServiceTest.php`
- **Purpose:** Unit tests for test discovery functionality
- **Implementation:**
  - Test file discovery in various directory structures
  - Verify test categorization accuracy
  - Test edge cases like empty directories

#### 2. `tests/Unit/Services/MarkdownReportServiceTest.php`
- **Purpose:** Unit tests for Markdown report generation
- **Implementation:**
  - Test report initialization and structure
  - Verify Markdown formatting accuracy
  - Test concurrent update handling

#### 3. `tests/Feature/Console/InteractiveTestRunnerCommandTest.php`
- **Purpose:** Feature tests for command interaction
- **Implementation:**
  - Test menu system navigation
  - Verify test selection workflows
  - Test command option handling

#### 4. `tests/Feature/Jobs/ExecuteTestSuiteJobTest.php`
- **Purpose:** Feature tests for test execution jobs
- **Implementation:**
  - Test parallel execution coordination
  - Verify progress tracking accuracy
  - Test error handling and recovery

#### 5. `docs/test-runner-user-guide.md`
- **Purpose:** User documentation for test runner
- **Implementation:**
  - Command usage examples and options
  - Report interpretation guide
  - Troubleshooting and FAQ section

#### 6. `docs/test-runner-developer-guide.md`
- **Purpose:** Developer documentation for extending test runner
- **Implementation:**
  - Architecture overview and service descriptions
  - Extension points and customization options
  - Contributing guidelines and code standards

## Implementation Timeline

### Week 1: Foundation (Phases 1-2)
- Set up command structure and basic menu system
- Implement test discovery and selection services
- Create interactive test selection interface

### Week 2: Execution Engine (Phases 3-4)
- Build parallel test execution system
- Implement job queue integration
- Create Markdown reporting with real-time updates

### Week 3: User Experience (Phases 5-6)
- Add progress tracking and console interface
- Implement comprehensive error handling
- Add recovery and retry mechanisms

### Week 4: Integration and Testing (Phases 7-8)
- Complete system integration and configuration
- Implement comprehensive test suite
- Create documentation and user guides

## Success Criteria

1. **Functional Requirements Met:** All specified functionality working as designed
2. **Performance Standards:** Parallel execution providing measurable performance improvement
3. **Reliability:** Robust error handling with graceful degradation
4. **Usability:** Intuitive interface requiring minimal user training
5. **Documentation:** Complete user and developer documentation
6. **Test Coverage:** Comprehensive test suite with high coverage
7. **Integration:** Seamless integration with existing EpochBridge codebase

## Risk Mitigation

1. **Process Management:** Implement proper process cleanup and resource management
2. **File System Operations:** Use atomic operations and proper locking for concurrent access
3. **Memory Usage:** Monitor memory consumption during parallel execution
4. **Error Propagation:** Ensure errors are properly caught and reported
5. **Performance Impact:** Monitor system resource usage during execution

This implementation plan provides a structured approach to building a comprehensive, production-ready test runner system for the EpochBridge application.
