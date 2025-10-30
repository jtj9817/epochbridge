# EpochBridge

A comprehensive calendar conversion platform built with Laravel 12 and the TALL Stack, supporting 19 calendar systems (15+ historical and modern calendars plus technical formats) with complete authentication and user management features.

## Features

### Calendar Conversion System
- **19 Calendar Systems**: Comprehensive support including:
  - **Modern**: Gregorian, Persian/Jalali, Islamic (Hijri), Hebrew
  - **Historical**: Julian, French Republican (with proleptic support), Coptic, Ethiopian, Egyptian
  - **Ancient**: Babylonian, Assyrian, Ancient Macedonian, Mayan
  - **East Asian**: Chinese Lunisolar, Japanese Era-based
- **Extensible Architecture**: Adapter pattern with dependency injection for calendar service customization
- **Multi-Format Output**: Unix timestamps, ISO 8601, Julian Day Numbers, and detailed calendar components
- **Real-time Conversion**: Instant conversion between any supported calendar systems
- **Historical Accuracy**: Authentic calculations with astronomical data integration

### User Experience
- **Interactive Landing Page**: Modern interface with comprehensive calendar displays
- **Conversion Management**: Save and manage conversions with persistent storage
  - **Authenticated Users**: Database-backed conversion history with full management
  - **Guest Users**: Browser localStorage persistence with automatic migration on login
  - **Complete Data Storage**: All 19 calendar systems saved for each conversion
  - **Duplicate Prevention**: Database constraints prevent redundant saves
  - **Notes**: Annotate saved conversions with custom notes (up to 2048 characters)
    - Modal-based interface for adding notes during save
    - Edit existing notes with dedicated modal component
    - Full CRUD operations with server-side validation
    - Guest and authenticated note support with migration on login
- **Toast Notifications**: Real-time feedback for all operations (save, delete, errors)
  - Auto-dismissing notifications with manual close option
  - Success/error states with contextual messages
  - Seamless Livewire-Alpine event bridge integration
- **Clipboard Integration**: Copy conversion results with one click
- **Quick Actions**: Today, Yesterday, Tomorrow buttons for rapid navigation
- **Date Input Tracking**: Smart save state management based on current date input
- **Responsive Design**: Optimized for desktop and mobile devices

### Technical Stack
- **TALL Stack Architecture**: Tailwind CSS 4, Alpine.js, Livewire 3, Laravel 12
- **Authentication System**: Complete auth flow with Laravel Fortify and OAuth integration
  - **Google Sign-In**: Seamless authentication with Google OAuth 2.0
  - **Traditional Login**: Email/password authentication with Fortify
  - **Account Linking**: Link social accounts to existing user accounts
- **Two-Factor Authentication**: Enhanced security with recovery codes
- **Email Verification**: Required for new user accounts (auto-verified for OAuth)
- **Modern UI**: Built with Flux UI component library
- **Livewire Volt**: Single-file components for rapid development

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS 4
- **Database**: MySQL 8.0
- **Build Tool**: Vite with HMR
- **OAuth**: Laravel Socialite (Google provider)
- **Testing**: PestPHP, Pest Browser Plugin, Laravel Dusk
- **Containerization**: Laravel Sail (Docker)

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- Node.js & npm
- Docker (for Sail)

### Installation

1. Clone the repository
```bash
git clone <repository-url>
cd epochbridge
```

2. Install dependencies
```bash
composer install
npm install
```

3. Set up environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure Google OAuth (optional)
```bash
# Add to .env file:
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback
```

To obtain Google OAuth credentials:
1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing
3. Enable Google+ API
4. Create OAuth 2.0 credentials
5. Add authorized redirect URI: `http://localhost:8080/auth/google/callback`

5. Start the development environment
```bash
# Using Laravel Sail (recommended)
./vendor/bin/sail up

# Or using Composer script
composer run dev
```

6. Run migrations
```bash
./vendor/bin/sail artisan migrate
```

7. Start the frontend development server
```bash
npm run dev
```

The application will be available at:
- **Laravel**: http://localhost:8080
- **Vite Dev Server**: http://localhost:5174

## Development Commands

### Laravel Sail (Docker)
```bash
# Start development environment
./vendor/bin/sail up

# Run artisan commands
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan make:model Example

# Stop environment
./vendor/bin/sail down
```

### Composer Scripts
```bash
# Start full development environment (server, queue, logs, vite)
composer run dev

# Run tests with config clear
composer run test
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run browser tests
php artisan dusk
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# View logs with Pail
php artisan pail
```

## Interactive Test Runner

EpochBridge features a comprehensive interactive test runner system with parallel execution, real-time progress tracking, error recovery, and detailed Markdown reporting.

### Features

- **Parallel Test Execution**: Run multiple test suites simultaneously with configurable process limits
- **Real-time Progress Tracking**: Live progress bars showing completion status, pass/fail counts, and ETAs
- **Interactive Test Selection**: Choose specific test suites or run comprehensive test workflows
- **Error Recovery System**: Automatic retry logic with exponential backoff for transient failures
- **Markdown Reporting**: Detailed GitHub-flavored reports with failure analysis and execution summaries
- **Verbose Logging**: Comprehensive logging of test execution with configurable output levels

### Usage

Run the interactive test runner:

```bash
# Interactive mode with menu selection
php artisan test:runner

# Direct execution with custom report file
php artisan test:runner --output-file=storage/reports/test-report.md

# Verbose mode with detailed logging
php artisan test:runner --verbose
```

### Test Runner Components

The test runner system includes several specialized services:

- **TestDiscoveryService**: Automatically discovers and categorizes test files across Unit, Feature, and Browser test suites
- **TestExecutionService**: Handles parallel test execution with process management and result collection
- **ProgressTrackingService**: Provides real-time progress updates with visual progress bars and statistics
- **ErrorRecoveryService**: Implements retry logic and failure analysis for robust test execution
- **MarkdownReportService**: Generates comprehensive test reports in Markdown format with detailed analysis

### Configuration

Key settings live in `config/test-runner.php` and can be overridden with environment variables:

| Setting | Env Variable | Default | Description |
| --- | --- | --- | --- |
| Parallel mode | `TEST_RUNNER_PARALLEL` | `true` | Enable parallel test execution |
| Max processes | `TEST_RUNNER_PROCESSES` | `4` | Number of parallel test processes |
| Timeout (seconds) | `TEST_RUNNER_TIMEOUT` | `300` | Maximum execution time per test suite |
| Retry attempts | `TEST_RUNNER_RETRY_ATTEMPTS` | `1` | Number of retry attempts for failed tests |
| Report file | `TEST_RUNNER_REPORT_FILE` | `test-report.md` | Output file for Markdown reports |
| Verbose logging | `TEST_RUNNER_VERBOSE` | `false` | Enable detailed execution logging |

### Environment Profiles

The test runner supports environment-specific configurations:

- **Development**: Standard settings with retries enabled
- **CI**: Increased timeouts, retries disabled, verbose logging
- **Local**: Fast execution with minimal reporting

Use the `test-runner` config to customize paths, file patterns, and per-environment overrides. The `TestRunnerServiceProvider` applies these defaults automatically whenever test services are resolved.

## Architecture

### Authentication
- **Laravel Fortify**: Handles traditional authentication logic
- **Laravel Socialite**: OAuth authentication with Google Sign-In
- **Livewire Volt Components**: Modern single-file components for auth forms
- **Two-Factor Authentication**: TOTP with recovery codes
- **Email Verification**: Required for account activation (auto-verified for OAuth)
- **Social Account Linking**: Link multiple OAuth providers to one account

### TALL Stack Components
- **Tailwind CSS 4**: Utility-first CSS with `@source` directives
- **Alpine.js**: Client-side interactivity without server roundtrips
- **Livewire 3**: Full-stack framework for dynamic interfaces
- **Laravel 12**: Modern backend with PHP 8.2+ features

### Project Structure
```
app/
├── Console/Commands/       # Artisan commands
│   └── InteractiveTestRunnerCommand.php # Interactive test runner with parallel execution
├── Contracts/              # Interfaces and contracts
│   ├── Calendar/          # Calendar adapter interfaces
│   │   └── FrenchRepublicanCalendarAdapter.php # French Republican adapter interface
│   └── TestProcessFactory.php          # Process factory interface for testing
├── Events/                 # Laravel events
│   ├── TestCompleted.php              # Individual test completion event
│   ├── TestExecutionCompleted.php     # Test suite completion event
│   └── TestExecutionStarted.php       # Test execution start event
├── Exceptions/             # Custom exception handling
│   ├── CalendarConversionException.php   # Base calendar exception (abstract)
│   ├── CalendarMalformedDateException.php # Date format parsing errors
│   ├── CalendarRangeException.php        # Date range validation errors
│   ├── CalendarCalculationException.php  # Astronomical calculation failures
│   ├── CalendarValidationException.php   # Date component validation errors
│   └── TestExecutionException.php        # Test runner execution errors
├── Http/Controllers/        # HTTP controllers
│   └── Auth/               # Authentication controllers
│       └── SocialAuthController.php    # Google OAuth authentication
├── Jobs/                   # Laravel jobs
│   └── ExecuteTestSuiteJob.php        # Background test suite execution
├── Listeners/              # Event listeners
│   ├── UpdateMarkdownReport.php       # Updates test reports on completion
│   └── UpdateProgressDisplay.php      # Updates real-time progress displays
├── Livewire/               # Livewire components
├── Models/                 # Eloquent models
│   ├── User.php           # User model with saved conversions and OAuth
│   ├── SocialAccount.php  # OAuth provider accounts (encrypted tokens)
│   ├── SavedConversion.php # User conversion history with date casting and notes
│   ├── JapaneseEra.php    # Japanese era data
│   ├── HistoricalCalendarData.php # Ancient calendar reference data
│   └── MayanCalendarData.php # Mayan calendar calculations
├── Providers/              # Service providers
│   ├── EventServiceProvider.php      # Event and listener bindings
│   └── TestRunnerServiceProvider.php # Test runner service bindings
├── Services/               # Application services
│   ├── Calendar Services/  # Calendar conversion services
│   │   ├── DateTimeConverterService.php    # Main conversion orchestrator with caching
│   │   ├── CalendarConversionService.php   # Base conversion utilities
│   │   ├── JulianDayService.php           # Julian Day Number calculations (astronomical)
│   ├── JulianCalendarService.php      # Julian calendar (historical dates)
│   │   ├── HebrewCalendarService.php      # Hebrew calendar
│   │   ├── IslamicCalendarService.php     # Islamic/Hijri calendar
│   │   ├── ChineseCalendarService.php     # Chinese lunisolar calendar (orchestrator)
│   │   ├── Chinese/                       # Chinese calendar astronomical subsystem
│   │   │   ├── ChineseLunisolarCalendar.php  # Suì construction and lunation logic
│   │   │   ├── AstronomyCalculator.php       # Meeus-based Sun/Moon ephemerides with ΔT
│   │   │   └── LunarMonth.php                # Lunation data structure
│   │   ├── JapaneseCalendarService.php    # Japanese era-based calendar
│   │   ├── FrenchRepublicanService.php    # French Revolutionary calendar with DI
│   │   ├── CopticCalendarService.php      # Coptic calendar
│   │   ├── EthiopianCalendarService.php   # Ethiopian calendar
│   │   ├── EgyptianCalendarService.php    # Ancient Egyptian calendar
│   │   ├── BabylonianCalendarService.php  # Babylonian calendar
│   │   ├── AssyrianCalendarService.php    # Assyrian calendar
│   │   ├── AncientMacedonianService.php   # Ancient Macedonian calendar
│   │   ├── MayanCalendarService.php       # Mayan calendar system
│   │   └── AncientAstronomyService.php    # Astronomical calculations
│   └── Test Runner Services/  # Test execution and management
│       ├── TestDiscoveryService.php      # Discovers and categorizes tests
│       ├── TestSelectionService.php      # Interactive test suite selection
│       ├── TestExecutionService.php      # Parallel test execution management
│       ├── ProgressTrackingService.php   # Real-time progress tracking and UI
│       ├── ErrorRecoveryService.php      # Test failure recovery and retry logic
│       └── MarkdownReportService.php     # Comprehensive test report generation
└── Support/                # Support classes
    ├── Calendars/         # Calendar adapter implementations
    │   └── FrenchRepublicanExtCalendarAdapter.php # Concrete FR adapter
    └── TestProcessFactory.php           # Process factory implementation

resources/
├── css/                    # Tailwind CSS files
├── js/                     # JavaScript files
└── views/
    ├── components/         # Blade components
    │   ├── calendars/     # Individual calendar display components (20 calendars)
    │   │   ├── base-calendar-card.blade.php   # Base component template
    │   │   ├── gregorian-card.blade.php       # Modern calendars
    │   │   ├── jalali-card.blade.php
    │   │   ├── hebrew-card.blade.php
    │   │   ├── islamic-card.blade.php
    │   │   ├── chinese-card.blade.php         # East Asian calendars
    │   │   ├── japanese-card.blade.php
    │   │   ├── coptic-card.blade.php          # Historical calendars
    │   │   ├── ethiopian-card.blade.php
    │   │   ├── egyptian-card.blade.php
    │   │   ├── french-republican-card.blade.php
    │   │   ├── babylonian-card.blade.php      # Ancient calendars
    │   │   ├── assyrian-card.blade.php
    │   │   ├── macedonian-card.blade.php
    │   │   ├── mayan-card.blade.php
    │   │   ├── iso8601-card.blade.php         # Technical formats
    │   │   ├── julian-day-card.blade.php
    │   │   ├── modified-julian-day-card.blade.php
    │   │   └── unix-card.blade.php
    │   └── loading-skeleton.blade.php
    ├── livewire/          # Livewire Volt components
    │   ├── landing.blade.php              # Main calendar conversion interface
    │   ├── components/                    # Modular landing page sections
    │   │   ├── calendar-input-section.blade.php    # Date input and validation
    │   │   ├── calendar-quick-actions.blade.php    # Today/Yesterday/Tomorrow
    │   │   ├── calendar-results-grid.blade.php     # Main conversion display
    │   │   ├── calendar-search-filter.blade.php    # Calendar filtering
    │   │   ├── saved-conversions-section.blade.php # User history management
    │   │   ├── saved-conversion-item.blade.php     # Individual saved item
    │   │   ├── save-conversion-modal.blade.php     # Modal for saving with notes
    │   │   └── edit-note-modal.blade.php           # Shared note editing modal
    │   ├── auth/                          # Authentication forms
    │   └── settings/                      # User settings pages
    └── pages/             # Page-level Volt components

config/
└── calendars.php         # Calendar-specific configuration
    └── french_republican  # French Republican settings
        ├── apply_paris_offset (default: true)
        └── allow_proleptic (default: true)

database/
├── migrations/            # Database schema
│   ├── *_create_saved_conversions_table.php
│   ├── *_add_note_column_to_saved_conversions_table.php
│   ├── *_create_social_accounts_table.php
│   ├── *_modify_users_table_for_social_auth.php
│   ├── *_create_japanese_eras_table.php
│   ├── *_create_historical_calendar_data_table.php
│   └── *_create_mayan_calendar_data_table.php
└── seeders/              # Database seeders
    ├── JapaneseEraSeeder.php
    └── MayanCalendarDataSeeder.php

tests/
├── Feature/               # Feature tests
│   ├── Auth/             # Authentication flow tests
│   │   └── GoogleOAuthTest.php         # Google OAuth integration tests
│   ├── Console/          # Console command tests
│   │   └── InteractiveTestRunnerCommandTest.php # Test runner command tests
│   ├── Integration/      # Integration workflow tests
│   │   └── FullTestRunnerWorkflowTest.php       # End-to-end test runner tests
│   ├── Jobs/             # Background job tests
│   │   └── ExecuteTestSuiteJobTest.php         # Test suite execution job tests
│   ├── Livewire/         # Livewire component tests
│   │   ├── SaveConversionWithNoteTest.php      # Save operations with notes
│   │   ├── UpdateConversionNoteTest.php        # Note update workflows
│   │   └── Components/                          # Component-specific tests
│   │       ├── EditNoteModalTest.php            # Note editing modal tests
│   │       └── SaveNoteUpdateEventTest.php      # Note event handling tests
│   └── Settings/         # User settings tests
├── Unit/                  # Unit tests
│   ├── Models/           # Model tests
│   │   └── SavedConversionNoteTest.php         # Note field behavior tests
│   └── Services/         # Service layer tests
│       ├── Calendar Services/                   # Calendar conversion service tests
│       └── Test Runner Services/               # Test runner service tests
│           ├── ErrorRecoveryServiceTest.php    # Error recovery logic tests
│           ├── MarkdownReportServiceTest.php   # Report generation tests
│           ├── ProgressTrackingServiceTest.php # Progress tracking tests
│           └── TestDiscoveryServiceTest.php    # Test discovery tests
├── Browser/               # Dusk browser tests
│   ├── LandingPageTest.php              # Full page interaction tests
│   ├── ConversionNoteManagementTest.php # Complete note management workflows
│   └── Components/                       # Component-specific browser tests
└── Support/               # Test support utilities
    └── PromptSequence.php      # Interactive command testing utilities
```

## Key Features

### Multi-Calendar Conversion Engine
The core functionality is powered by `DateTimeConverterService` and 15+ specialized calendar services:

- **Universal Conversion**: Convert between any of the 19 supported calendar systems
- **Comprehensive Output**: Detailed components for each calendar including:
  - Year, month name, day, day of week in native scripts
  - English transliterations and romanizations
  - Cultural and historical context
  - Unix timestamps, ISO 8601, and Julian Day Numbers (astronomical reference)
- **Adapter Pattern Architecture**: Dependency injection for extensible calendar implementations
- **Historical Accuracy**: Authentic calculations using:
  - Astronomical data for lunar calendars
  - Historical era systems (Japanese, Roman, etc.)
  - Ancient calculation methods for historical calendars
- **Robust Error Handling**: Hierarchical exception system with specialized calendar exceptions:
  - `CalendarMalformedDateException` for parsing errors
  - `CalendarRangeException` for date boundary validation  
  - `CalendarCalculationException` for astronomical failures
  - `CalendarValidationException` for component validation
- **Timezone Aware**: Consistent processing across time zones

### Supported Calendar Systems

EpochBridge supports **19 calendar systems** across modern, historical, ancient, and technical formats:

#### Modern Calendars
- **Gregorian**: International standard civil calendar
- **Persian/Jalali**: Official calendar of Iran and Afghanistan  
- **Islamic (Hijri)**: Lunar calendar used in Islamic countries
- **Hebrew**: Jewish calendar with lunar-solar calculations

#### Historical Calendars
- **Julian**: Roman calendar, predecessor to Gregorian (historical date representation)
- **French Republican**: Revolutionary calendar (1793-1805) with proleptic extension and Paris mean solar offset
- **Coptic**: Egyptian Christian calendar
- **Ethiopian**: Traditional calendar of Ethiopia
- **Egyptian**: Ancient Egyptian civil calendar

#### Ancient Calendars
- **Babylonian**: Ancient Mesopotamian lunar calendar
- **Assyrian**: Ancient Assyrian calendar system
- **Ancient Macedonian**: Calendar of ancient Macedonia
- **Mayan**: Complex Mesoamerican calendar system

#### East Asian Calendars
- **Chinese Lunisolar**: Traditional Chinese calendar with astronomical calculations, Meeus algorithms for Sun/Moon ephemerides, ΔT corrections, and dìngqì solar term convention for accurate leap month detection
- **Japanese Era-based**: Calendar with imperial era names

#### Technical Formats
- **ISO 8601**: International date/time standard
- **Julian Day Number**: Astronomical day count (primary conversion backbone)
- **Modified Julian Day**: Reduced Julian Day variant
- **Unix Timestamp**: Seconds since Unix epoch (1970-01-01)

### User Features
- **Conversion Management**: Save and organize conversion history with full persistence
  - **Complete Storage**: All 19 calendar systems saved per conversion
  - **Date Tracking**: Input date and timestamp fields for accurate history
  - **Smart State Management**: Automatic save button state based on current date input
  - **Notes Support**: Add custom annotations to saved conversions (up to 2048 characters)
    - Modal interface for note creation and editing
    - Character counter with real-time validation
    - Full CRUD operations with proper error handling
- **Guest Features**: Full functionality without authentication
  - **Persistent Storage**: Browser localStorage with data migration on login
  - **Client-Side Rendering**: Fast display of guest conversion history
  - **Automatic Migration**: Seamless transfer of guest data (including notes) to database on authentication
  - **Note Support**: Guest users can add and edit notes in localStorage
- **Clipboard Integration**: One-click copying of conversion results
- **Search & Filter**: Find saved conversions by date or calendar type
- **Visual Feedback**: Table layout with custom color theme and optimized spacing

### Interactive Landing Page
Located at `resources/views/livewire/landing.blade.php` with modular component architecture:
- **Comprehensive Display**: All 19 calendar systems shown simultaneously using individual calendar cards
- **Immediate Loading**: All calendars loaded instantly without lazy-loading for optimal UX
- **Modular Design**: Separated into focused components for maintainability:
  - Calendar input section with validation and date tracking
  - Quick action buttons (Today, Yesterday, Tomorrow)  
  - Results grid with filtering and complete data display
  - Saved conversions management with table layout
  - Search and filter functionality
  - Individual saved conversion items with client-side rendering
- **Real-time Updates**: Instant conversion with Livewire Volt `on()` event listeners
- **Alpine.js Integration**: Proper method invocation for save operations and state management
- **Visual Design**: Unique styling for each calendar system with cultural icons and custom color theme
- **Responsive Layout**: Optimized grid system for all device sizes
- **Loading States**: Smooth UX with skeleton components and loading indicators

### Livewire Volt
Single-file components combining PHP logic and Blade templates with modern event handling:
- **Landing page**: `resources/views/livewire/landing.blade.php` - Main date conversion interface
- **Modular components**: `resources/views/livewire/components/` - Reusable sections:
  - `calendar-input-section.blade.php` - Date input with validation
  - `calendar-quick-actions.blade.php` - Navigation shortcuts
  - `calendar-results-grid.blade.php` - Main conversion display
  - `calendar-search-filter.blade.php` - Calendar filtering UI
  - `saved-conversions-section.blade.php` - History management
  - `save-conversion-modal.blade.php` - Save modal with note support
  - `edit-note-modal.blade.php` - Shared note editing component
- **Authentication forms**: `resources/views/livewire/auth/` - Login, register, password reset, 2FA
- **Settings pages**: `resources/views/livewire/settings/` - User profile, password, appearance, 2FA management
- **Page components**: `resources/views/pages/` - Additional page-level components

### Flux UI Integration
Pre-built component library providing:
- Consistent design system
- Accessible UI components
- Theme customization support

## Environment Configuration

### Docker Ports
- **Laravel**: Port 8080
- **MySQL**: Port 3307
- **Vite**: Port 5174

### Database
- **Development**: MySQL 8.0 via Docker
- **Testing**: Separate `testing` database
- **Migrations**: Standard Laravel migration system

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Run tests: `composer run test`
5. Format code: `./vendor/bin/pint`
6. Submit a pull request

## Testing

The project uses PestPHP for testing with comprehensive coverage:
- **Feature Tests**: Authentication flows (traditional + OAuth), settings, dashboard, note management
- **Browser Tests**: Full user interactions with Dusk including note CRUD workflows
- **Unit Tests**: Individual component testing including model behavior with notes
- **Component Tests**: Livewire component testing with note modal interactions
- **OAuth Tests**: Google Sign-In integration with mocked providers

Run the full test suite:
```bash
php artisan test
```

Note-specific test coverage:
```bash
# Browser tests for complete note workflows
php artisan test tests/Browser/ConversionNoteManagementTest.php

# Feature tests for note operations
php artisan test tests/Feature/Livewire/SaveConversionWithNoteTest.php
php artisan test tests/Feature/Livewire/UpdateConversionNoteTest.php

# Component tests for note modals
php artisan test tests/Feature/Livewire/Components/EditNoteModalTest.php

# Unit tests for note model behavior
php artisan test tests/Unit/Models/SavedConversionNoteTest.php
```

## Production Deployment

1. Build assets for production
```bash
npm run build
```

2. Optimize Laravel
```bash
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

3. Set environment variables
```bash
APP_DEBUG=false
APP_ENV=production
```

## License

[Add your license information here]

## Support

[Add support information here]
