# Changelog

All notable changes to EpochBridge will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [October 14, 2025] - Calendar Architecture Enhancements & Test Improvements

### Added
- **Julian Calendar System**: Complete historical calendar implementation alongside Julian Day calculations
  - `app/Services/JulianCalendarService.php` - Historical Julian calendar date conversions
  - Separate from Julian Day Number astronomical calculations for clarity
  - `resources/views/components/card/julian-card.blade.php` - UI component for Julian calendar display
  - Comprehensive test coverage in feature tests
  
- **Adapter Pattern with Dependency Injection**: Extensible architecture for complex calendar systems
  - `app/Contracts/Calendar/FrenchRepublicanCalendarAdapter.php` - Adapter interface defining calendar operations
  - `app/Support/Calendars/FrenchRepublicanExtCalendarAdapter.php` - Concrete implementation with caching
  - Per-instance memoization for improved performance without global state
  - Container binding in `AppServiceProvider` for proper dependency injection
  
- **French Republican Calendar Enhancements**: Complete feature implementation per enhancement plan
  - **Proleptic Calendar Support**: Mathematical extension beyond historical 1792-1805 range
  - **Paris Mean Solar Offset**: Configurable UT1 + 00:09:21 offset for historical accuracy
  - **Configuration System**: `config/calendars.php` with feature flags
    - `apply_paris_offset` (default: true) - Apply historical Paris Observatory offset
    - `allow_proleptic` (default: true) - Enable extended date range calculations
  - Historical equinox verification with test fixtures in `tests/__fixtures/french_republican_year_starts.php`
  
- **Component Test Infrastructure**: Enhanced testing for Livewire components
  - `tests/Feature/Livewire/Components/CalendarResultsGridTest.php` - Dedicated calendar grid tests
  - Proper test isolation following Livewire 3 testing patterns
  - Mock data alignment with service contracts

### Changed
- **FrenchRepublicanService Architecture**: Refactored to use dependency injection
  - Constructor injection of `FrenchRepublicanCalendarAdapter` interface
  - Resolved from container via `app()` helper in `DateTimeConverterService`
  - Enhanced testability with mockable adapter interface
  
- **DateTimeConverterService**: Updated to support new calendar services
  - Added `JulianCalendarService` to available calendars
  - French Republican service now resolved from container for DI support
  - Distinction between Julian calendar (historical) and Julian Day Number (astronomical)

- **Test Architecture**: Improved component test patterns
  - Parent component tests validate state changes rather than child output
  - Proper Livewire Volt component hierarchy handling
  - Fixed test isolation issues for reliable test execution

### Fixed
- **Livewire Component Test Isolation**: Resolved hierarchy testing issues
  - Parent tests now properly validate state rather than rendered child components
  - Follows Livewire 3 patterns where child components aren't rendered in parent tests
  - `tests/Feature/Livewire/LandingPageTest.php` refactored for proper isolation
  
- **French Republican Year Calculation**: Corrected historical data
  - Fixed Year 11 start date in test fixtures
  - Validated against known Paris equinox observations
  - Proper handling of complementary days in leap vs common years

- **Calendar Results Grid Filtering**: Enhanced null and scalar value handling
  - Proper filtering for scalar calendar values
  - Updated mock data to match service contracts
  - Improved component rendering reliability

### Technical Improvements
- **Extensible Architecture**: Adapter pattern demonstrates best practices for complex calendars
  - Easy to mock for unit tests
  - Swap calculation strategies (astronomical vs arithmetic)
  - Decouple from specific library implementations
  - Per-instance caching improves performance
  
- **Configuration Management**: Centralized calendar-specific settings
  - `config/calendars.php` provides single source of truth
  - Environment variable support for deployment flexibility
  - Feature flags enable/disable specific behaviors
  
- **Historical Accuracy**: Enhanced calendar calculations
  - Paris mean solar offset for French Republican calendar
  - Historical equinox data validation
  - Proleptic extensions with mathematical rigor

### Documentation
- **Architecture Documentation**: Comprehensive updates to reflect new patterns
  - Added "Adapter Pattern for Calendar Services" section in `docs/architecture-overview.md`
  - New ADR-005: Adapter Pattern for Calendar Services
  - Detailed explanation of Julian calendar vs Julian Day Number distinction
  
- **Integration Guide**: Extensive adapter pattern documentation
  - Step-by-step guide for implementing adapter pattern in `docs/calendar-integration-guide.md`
  - Code examples for interface definition, concrete implementation, DI setup
  - Testing patterns with mocked adapters
  - Real-world French Republican calendar example
  
- **Enhancement Plan Completion**: Marked all French Republican objectives as completed
  - Updated `docs/french-republican-service-enhancement-plan.md` with implementation details
  - Documented all completed tasks with commit references
  - Added resolved questions and future enhancement suggestions
  
- **README Updates**: Reflected new features and architecture
  - Updated project structure with `Contracts/Calendar/` and `Support/Calendars/`
  - Added configuration documentation
  - Clarified Julian calendar vs Julian Day Number
  - Mentioned proleptic support and adapter pattern

### Testing
- **Unit Tests**: Expanded French Republican calendar test coverage
  - Round-trip conversion tests across date ranges
  - Boundary condition testing (epoch start/end, month boundaries)
  - Complementary day validation (leap vs common years)
  - Display formatting assertions
  
- **Feature Tests**: Enhanced Livewire component testing
  - Calendar results grid component tests
  - Landing page refactored for proper component isolation
  - Configuration behavior validation
  
- **Test Fixtures**: Historical reference data for validation
  - `tests/__fixtures/french_republican_year_starts.php` with Years I-XIV equinox dates
  - Enables verification against known historical observations

## [October 11, 2025] - Interactive Test Runner System Implementation

### Added
- **Interactive Test Runner Command**: Comprehensive parallel test execution system
  - `app/Console/Commands/InteractiveTestRunnerCommand.php` - Interactive CLI with menu selection
  - `php artisan test:runner` command with verbose logging and custom output file support
  - Interactive test suite selection with comprehensive workflow options
  - Real-time progress tracking with ANSI-friendly progress bars and ETA calculations

- **Test Runner Service Architecture**: Modular service layer for test execution management
  - `app/Services/TestDiscoveryService.php` - Automatic test file discovery and categorization
  - `app/Services/TestSelectionService.php` - Interactive test suite selection interface
  - `app/Services/TestExecutionService.php` - Parallel test execution with process management
  - `app/Services/ProgressTrackingService.php` - Real-time progress updates and statistics
  - `app/Services/ErrorRecoveryService.php` - Test failure recovery with exponential backoff
  - `app/Services/MarkdownReportService.php` - GitHub-flavored Markdown report generation

- **Background Job System**: Queue-based test execution for scalability
  - `app/Jobs/ExecuteTestSuiteJob.php` - Background test suite execution with timeout management
  - `app/Contracts/TestProcessFactory.php` - Process factory interface for testability
  - `app/Support/TestProcessFactory.php` - Symfony process factory implementation

- **Event-Driven Architecture**: Real-time updates and reporting
  - `app/Events/TestExecutionStarted.php` - Test suite execution start events
  - `app/Events/TestCompleted.php` - Individual test completion events with results
  - `app/Events/TestExecutionCompleted.php` - Suite completion with comprehensive results
  - `app/Listeners/UpdateProgressDisplay.php` - Real-time progress display updates
  - `app/Listeners/UpdateMarkdownReport.php` - Automatic report generation on completion

- **Configuration System**: Environment-specific test runner configuration
  - `config/test-runner.php` - Comprehensive configuration with environment profiles
  - `app/Providers/TestRunnerServiceProvider.php` - Service provider for dependency injection
  - Support for parallel execution limits, timeouts, retry attempts, and report customization

- **Exception Handling**: Specialized test execution error management
  - `app/Exceptions/TestExecutionException.php` - Test runner specific exceptions
  - Detailed error context and recovery information for debugging

### Changed
- **Testing Architecture**: Enhanced test structure with comprehensive coverage
  - Reorganized test suites into Unit, Feature, Integration, and Browser categories
  - Added test runner service testing with mock implementations
  - Improved test isolation and parallel execution compatibility

- **Service Provider Registration**: Enhanced application bootstrapping
  - Added `TestRunnerServiceProvider` to service provider registration
  - Enhanced `EventServiceProvider` with test runner event listeners

### Fixed
- **Process Management**: Robust process handling and cleanup
  - Implemented proper process factory pattern for testable process management
  - Added timeout handling and graceful shutdown for parallel test execution
  - Fixed process resource cleanup and memory management

### Technical Improvements
- **Parallel Execution**: High-performance test execution with configurable process limits
- **Progress Tracking**: Real-time visual progress bars with completion statistics
- **Error Recovery**: Intelligent retry logic with exponential backoff and failure analysis
- **Report Generation**: Detailed Markdown reports with GitHub-flavored formatting
- **Configuration Management**: Environment-specific settings with override capabilities
- **Test Coverage**: Comprehensive test suite for all test runner components

### Testing
- **Feature Tests**: End-to-end test runner workflow testing
  - `tests/Feature/Console/InteractiveTestRunnerCommandTest.php` - Command interface testing
  - `tests/Feature/Integration/FullTestRunnerWorkflowTest.php` - Complete workflow testing
  - `tests/Feature/Jobs/ExecuteTestSuiteJobTest.php` - Background job testing

- **Unit Tests**: Service layer testing with mock implementations  
  - `tests/Unit/Services/TestDiscoveryServiceTest.php` - Test discovery logic testing
  - `tests/Unit/Services/ProgressTrackingServiceTest.php` - Progress tracking testing
  - `tests/Unit/Services/ErrorRecoveryServiceTest.php` - Error recovery logic testing
  - `tests/Unit/Services/MarkdownReportServiceTest.php` - Report generation testing

- **Support Utilities**: Testing infrastructure enhancements
  - `tests/Support/PromptSequence.php` - Interactive command testing utilities

## [October 09, 2025] - Exception Handling Architecture & Component Testing

### Added
- **Hierarchical Exception System**: Comprehensive calendar exception handling with PSR-4 compliance
  - `CalendarConversionException.php` - Abstract base class extending `DateException`
  - `CalendarMalformedDateException.php` - Handles malformed date string parsing errors
  - `CalendarRangeException.php` - Manages date boundary and range validation errors
  - `CalendarCalculationException.php` - Handles astronomical calculation failures
  - `CalendarValidationException.php` - Manages date component validation errors
  - Calendar system context in error messages with `[{calendarSystem}]` prefix
  - Support for date component debugging information in exception constructors

### Changed
- **Exception Architecture Refactoring**: Separated monolithic exception into specialized classes
  - Moved from single-file exception to individual files for better PSR-4 autoloading
  - Enhanced error context with calendar system identification
  - Improved debugging capabilities with date component tracking
  - Better error categorization for specific handling scenarios

### Fixed
- **PSR-4 Autoloading**: Resolved autoloading issues with properly separated exception files
- **Exception Inheritance**: Corrected exception hierarchy with proper PHP built-in exception extends

### Technical Improvements
- **Code Organization**: Clean separation of exception concerns into focused classes
- **Error Handling**: Enhanced error context and debugging information
- **Maintainability**: Individual exception files improve code maintainability
- **Documentation**: Updated architecture documentation with complete exception hierarchy

## [October 09, 2025] - Component Architecture Modernization & Testing Enhancement

### Added
- **Comprehensive Component Testing Suite**: Full browser testing coverage for modular components
  - `tests/Browser/LandingPageTest.php` - Complete landing page interaction testing
  - `tests/Browser/Components/CalendarQuickActionsTest.php` - Quick action button testing
  - `tests/Browser/Components/CalendarResultsGridTest.php` - Results grid functionality testing
  - `tests/Browser/Components/CalendarSearchFilterTest.php` - Calendar filtering testing
  - `tests/Browser/Components/SavedConversionsSectionTest.php` - User history management testing
- **Enhanced Calendar Service Tests**: Unit tests for newly implemented calendar systems
  - `tests/Unit/Services/CopticCalendarServiceTest.php` - Coptic calendar conversion testing
  - `tests/Unit/Services/EgyptianCalendarServiceTest.php` - Ancient Egyptian calendar testing
  - `tests/Unit/Services/EthiopianCalendarServiceTest.php` - Ethiopian calendar testing
- **Database Factory Expansion**: `SavedConversionFactory.php` for comprehensive test data generation
- **Development Documentation**: New comprehensive guides for component development
  - `docs/calendar-integration-guide.md` - Calendar system integration patterns
  - `docs/component-architecture.md` - Modular component design principles
  - `docs/component-testing-guide.md` - Testing strategies for components
  - `docs/cultural-accuracy-guide.md` - Cultural authenticity guidelines
  - `docs/performance-guide.md` - Performance optimization patterns

### Changed
- **Livewire Volt Event System**: Modernized event handling with new `on()` helper
  - Replaced legacy event listener syntax with modern Livewire Volt patterns
  - Enhanced component communication between modular sections
  - Improved real-time updates and user interaction responsiveness
- **Component Architecture**: Complete modularization of landing page functionality
  - Extracted calendar input section into `calendar-input-section.blade.php`
  - Separated quick actions into `calendar-quick-actions.blade.php`
  - Isolated results display into `calendar-results-grid.blade.php`
  - Created dedicated filtering UI in `calendar-search-filter.blade.php`
  - Organized saved conversions into `saved-conversions-section.blade.php`
  - Added individual conversion items as `saved-conversion-item.blade.php`
- **SavedConversion Model Enhancement**: Added automatic date casting for `gregorian_date` field
  - Improved data handling with proper type casting
  - Enhanced query performance with optimized attribute access
  - Better integration with calendar conversion display components
- **DateTimeConverterService Optimization**: Implemented caching and calendar categorization
  - Added conversion result caching for improved performance
  - Implemented calendar system categorization (modern, historical, ancient, technical)
  - Enhanced lazy loading support for complex calendar calculations

### Fixed
- **PHP 8.4 Compatibility**: Resolved syntax errors in Livewire Volt components
  - Fixed ParseError issues with proper state declaration syntax
  - Updated component method definitions for PHP 8.4 compatibility
  - Corrected property initialization patterns
- **Event Listener Modernization**: Updated deprecated event handling patterns
  - Replaced outdated Livewire event syntax with modern alternatives
  - Fixed component communication issues between modular sections
  - Improved event propagation and data handling

### Technical Improvements
- **Component Separation**: Clean extraction of concerns into focused, reusable components
- **Testing Coverage**: Comprehensive test suite covering all new modular components
- **Code Organization**: Consistent patterns across all newly created components
- **Performance**: Optimized component loading with lazy loading and caching strategies
- **Documentation**: Extensive component architecture and testing documentation
- **Development Workflow**: Enhanced development experience with better component isolation

## [October 08, 2025] - Massive Calendar System Expansion & User Features

### Added
- **15+ Calendar Systems**: Complete implementation of comprehensive calendar conversion platform
  - **Modern Calendars**: Hebrew, Islamic (Hijri) lunar calendar systems  
  - **Historical Calendars**: Julian, French Republican, Coptic, Ethiopian, Egyptian calendars
  - **Ancient Calendars**: Babylonian, Assyrian, Ancient Macedonian, Mayan calendar systems
  - **East Asian Calendars**: Chinese Lunisolar with zodiac, Japanese Era-based calendar
- **Calendar Service Architecture**: Specialized service classes for each calendar system
  - `HebrewCalendarService.php` - Jewish calendar with lunar-solar calculations
  - `IslamicCalendarService.php` - Hijri lunar calendar conversions  
  - `ChineseCalendarService.php` - Chinese lunisolar with 60-year cycle
  - `JapaneseCalendarService.php` - Imperial era-based calendar system
  - `FrenchRepublicanService.php` - Revolutionary calendar (1793-1805)
  - `CopticCalendarService.php`, `EthiopianCalendarService.php`, `EgyptianCalendarService.php`
  - `BabylonianCalendarService.php`, `AssyrianCalendarService.php`, `AncientMacedonianService.php`
  - `MayanCalendarService.php` - Complex Mesoamerican calendar system
  - `AncientAstronomyService.php` - Astronomical calculations for ancient calendars
- **Advanced Calendar Data Models**: Database integration for historical accuracy
  - `JapaneseEra.php` model with era names and date ranges
  - `HistoricalCalendarData.php` for ancient calendar reference data
  - `MayanCalendarData.php` for complex Mayan calendar calculations
  - `SavedConversion.php` for user conversion history management
- **User Conversion Management**: Complete save/load functionality
  - Authenticated users can save and organize conversion history
  - Guest users utilize browser local storage for temporary saves
  - Bulk save operations for multiple conversions
  - Conversion history with search and filtering capabilities
- **Enhanced Landing Page Interface**: Comprehensive multi-calendar display
  - Simultaneous display of all 15+ calendar systems
  - Unique visual styling for each calendar with cultural icons
  - Real-time conversion updates across all calendars
  - Responsive grid layout optimized for desktop and mobile
- **Clipboard Integration**: One-click copy functionality for all conversion results
- **Database Schema Extensions**: 
  - `japanese_eras` table with comprehensive era data
  - `historical_calendar_data` table for ancient calendar reference
  - `mayan_calendar_data` table for Mayan calendar calculations
  - `saved_conversions` table for user history management
- **Comprehensive Test Coverage**: Unit tests for all new calendar services
  - `ChineseCalendarServiceTest.php`, `JapaneseCalendarServiceTest.php`
  - `CopticCalendarServiceTest.php`, `EgyptianCalendarServiceTest.php`, `EthiopianCalendarServiceTest.php`
- **Julian Day Integration**: Universal calendar conversion backbone
  - `JulianDayService.php` for astronomical calculations
  - Modified Julian Day support for modern applications
  - Accurate conversion between all calendar systems

### Changed
- **Landing Page Architecture**: Complete redesign for multi-calendar display
  - Replaced simple dual-calendar interface with comprehensive 15+ calendar grid
  - Enhanced UX with loading states and error handling
  - Improved visual hierarchy with calendar-specific styling
- **DateTimeConverterService**: Expanded to orchestrate all calendar conversions
  - Universal conversion capability between any supported calendars
  - Enhanced output with Julian Day Numbers and astronomical data
  - Improved error handling across all calendar systems
- **Authentication Integration**: Better user experience for conversion management
  - Seamless transition between guest and authenticated storage
  - Enhanced user profile with saved conversion statistics
- **Database Architecture**: Expanded schema to support historical calendars
  - Migration system for new calendar data tables
  - Seeder classes for Japanese eras and Mayan calendar data

### Fixed
- **Calendar Accuracy**: Replaced undefined calendar functions with proper ext-calendar API
- **Islamic Calendar**: Improved lunar calculations with proper ext-calendar integration
- **Date Input Logic**: Consolidated calendar input fields and validation
- **Julian Day Conversion**: Corrected calculations and improved results display
- **Authentication Helper**: Replaced auth() helper with Auth facade for better IDE support

### Technical Improvements
- **Service Layer Architecture**: Clean separation of calendar-specific logic
- **Database Design**: Normalized structure for historical calendar data  
- **Code Organization**: Consistent patterns across all calendar services
- **Performance Optimization**: Efficient calculation methods for complex calendars
- **Documentation**: Comprehensive calendar systems reference documentation
- **Testing Strategy**: Robust test coverage for all calendar conversions

## [October 07, 2025] - Major Feature Implementation: Date Conversion System

### Added
- **DateTimeConverterService** (`app/Services/DateTimeConverterService.php`) - Core business logic for multi-calendar date conversion
  - Converts dates between Gregorian and Persian/Jalali calendars
  - Returns comprehensive date information including year, month names, day of week
  - Provides English transliterations for Persian calendar names
  - Outputs Unix timestamps and ISO 8601 formatted dates
  - Robust input validation with exception handling
- **Landing Page** (`resources/views/livewire/landing.blade.php`) - Interactive date conversion interface
  - Livewire Volt single-file component architecture
  - Real-time date conversion with form validation
  - Quick-action buttons for Today, Yesterday, Tomorrow
  - Loading states and error handling
  - Responsive design with Tailwind CSS
- **Comprehensive Testing Suite**
  - Unit tests for `DateTimeConverterService` (`tests/Unit/Services/DateTimeConverterServiceTest.php`)
  - Feature tests for landing page functionality (`tests/Feature/Livewire/LandingPageTest.php`)
  - Test coverage for date conversion logic and user interactions
- **Persian Calendar Integration**
  - Authentic Jalali month names: Farvardin, Ordibehesht, Khordad, etc.
  - Persian day names with English transliterations
  - Cultural-appropriate date formatting
- **Public Layout System** (`resources/views/components/layouts/public.blade.php`)
  - Dedicated layout for public-facing pages
  - Authentication-free access for landing page

### Changed
- **Project Structure**: Added `app/Services/` directory for business logic encapsulation
- **Routing Configuration**: Landing page now serves as application homepage (`/` route)
- **TALL Stack Implementation**: Enhanced Livewire Volt usage with advanced state management
- **User Interface**: Applied retro-computing aesthetic with modern design principles
- **Error Handling**: Improved validation messages and user feedback systems

### Fixed
- **Livewire Volt Syntax**: Resolved ParseError issues with proper state declaration syntax
- **Validation Methods**: Corrected Livewire Volt validation message handling
- **Authentication Flow**: Fixed authentication errors on public landing page
- **Date Input Styling**: Centered convert date button and improved form layout
- **Jalali Date Methods**: Replaced undefined methods with proper format calls

### Technical Improvements
- **Code Quality**: Implemented proper separation of concerns with service layer
- **Type Safety**: Added comprehensive type hints and documentation
- **Performance**: Optimized date conversion algorithms
- **Maintainability**: Structured code following Laravel best practices
- **Testing Strategy**: Established comprehensive test coverage for critical functionality

### Documentation
- **Architecture Overview**: Created comprehensive technical documentation
- **Implementation Plans**: Documented API and landing page implementation strategies
- **Calendar Documentation**: Extensive documentation of Persian calendar integration
- **Development Guidelines**: Updated project instructions and development workflows

## [October 03, 2025] - Foundation & Authentication System

### Added
- **Initial Laravel 12 Application Setup**
  - TALL Stack (Tailwind CSS 4, Alpine.js, Livewire 3, Laravel 12) foundation
  - Laravel Sail Docker development environment
  - Vite build system with Tailwind CSS integration
- **Complete Authentication System**
  - Laravel Fortify integration for authentication logic
  - Two-factor authentication with TOTP and recovery codes
  - Email verification requirement for new accounts
  - Password reset functionality
  - Livewire Volt components for all authentication forms
- **User Management Features**
  - User registration and login
  - Profile management
  - Password update functionality
  - Account deletion with confirmation
  - Settings interface with multiple sections
- **UI/UX Foundation**
  - Flux UI component library integration
  - Responsive design system
  - Multiple layout variants (app, auth, public)
  - Modern component architecture

### Infrastructure
- **Database Setup**
  - MySQL 8.0 via Docker configuration
  - User table with two-factor authentication columns
  - Migration system for schema management
  - Factory patterns for testing data
- **Testing Infrastructure**
  - PestPHP testing framework setup
  - Laravel Dusk for browser testing
  - Comprehensive test suite for authentication flows
  - Separate testing database configuration
- **Development Tools**
  - Laravel Pint for code formatting
  - Laravel Pail for log monitoring
  - Docker Compose configuration for development
  - Custom Composer scripts for development workflow

---

## Future Releases

### Planned Features
- **Enhanced Calendar Support**: Additional calendar systems beyond Gregorian and Jalali
- **API Endpoints**: RESTful API for programmatic date conversion access
- **Bulk Conversion**: Upload and convert multiple dates simultaneously
- **Historical Date Accuracy**: Enhanced historical date conversion precision
- **User Preferences**: Customizable default calendar systems and formats
- **Export Functionality**: Download conversion results in various formats (CSV, JSON, XML)

### Technical Roadmap
- **Performance Optimization**: Caching layer for frequently converted dates
- **Internationalization**: Multi-language support for UI elements
- **Mobile App**: Native mobile applications for iOS and Android
- **Third-party Integrations**: Calendar application plugins and integrations
- **Advanced Analytics**: Usage statistics and conversion pattern analysis