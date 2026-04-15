# EpochBridge Architecture Overview

## Table of Contents
1. [Application Overview](#application-overview)
2. [Technology Stack](#technology-stack)
3. [System Architecture](#system-architecture)
4. [Application Layers](#application-layers)
5. [TALL Stack Implementation](#tall-stack-implementation)
6. [Authentication Architecture](#authentication-architecture)
7. [Calendar System Integration](#calendar-system-integration)
8. [Database Architecture](#database-architecture)
9. [Frontend Architecture](#frontend-architecture)
10. [Testing Architecture](#testing-architecture)
11. [Build & Deployment Architecture](#build--deployment-architecture)
12. [Security Architecture](#security-architecture)
13. [Performance Considerations](#performance-considerations)

## Application Overview

EpochBridge is a comprehensive calendar conversion platform built using the TALL Stack (Tailwind CSS, Alpine.js, Livewire, Laravel) architecture. The application supports 19 calendar systems (15+ historical and modern calendars plus technical formats) with complete user management, persistent conversion history for both authenticated and guest users, and seamless data migration.

### Core Purpose
- **Primary Function**: Universal calendar conversion between 19 calendar systems including modern, historical, ancient calendars, and technical formats
- **Target Users**: Historians, developers, researchers, genealogists, and anyone working with diverse calendar systems
- **Key Features**: 
  - Real-time conversion between 19 calendar systems
  - Historical accuracy with astronomical calculations
  - Persistent conversion storage for authenticated and guest users
  - Guest data migration to database on authentication
  - Complete calendar data storage (all 19 systems per conversion)
  - Notes support for annotating saved conversions (up to 2048 characters)
  - Modal-based interface for adding and editing notes
  - Guest and authenticated note support with migration on login
  - Clipboard integration for easy data export
  - Comprehensive authentication and user profiles
  - Date input tracking for smart save state management

## Technology Stack

### Backend Technologies
- **Framework**: Laravel 12 (PHP 8.2+)
- **Authentication**: Laravel Fortify 1.30+
- **Interactive Components**: Livewire 3 with Volt
- **Database**: MySQL 8.0 (via Docker)
- **Calendar Libraries**: 
  - `fisharebest/ext-calendar` 2.6+ (Hebrew, Islamic, French Republican calendars)
  - `morilog/jalali` 3.* (Persian/Jalali calendar support)
- **Custom Calendar Services**: 15+ specialized services for historical and ancient calendars

### Frontend Technologies
- **CSS Framework**: Tailwind CSS 4 with Vite integration
- **JavaScript Framework**: Alpine.js (auto-included with Livewire 3)
- **UI Component Library**: Flux UI 2.1+
- **Build Tool**: Vite with Hot Module Replacement (HMR)

### Development & Testing
- **Testing Framework**: PestPHP 4.1+ with Laravel plugin
- **Browser Testing**: Laravel Dusk 8.2+
- **Code Quality**: Laravel Pint 1.18+ (PHP CS Fixer)
- **Development Containerization**: Laravel Sail 1.41+ (Docker)
- **Production Containerization**: Multi-stage Dockerfile with PHP 8.2-FPM, Nginx, Supervisord
- **Logging**: Laravel Pail 1.2+

## System Architecture

### High-Level Architecture Pattern
EpochBridge follows a **Layered Monolithic Architecture** with clear separation of concerns:

```
┌─────────────────────────────────────┐
│           Presentation Layer        │
│     (Livewire Volt Components)      │
├─────────────────────────────────────┤
│            Service Layer            │
│      (Business Logic Services)      │
├─────────────────────────────────────┤
│           Repository Layer          │
│         (Eloquent Models)           │
├─────────────────────────────────────┤
│            Data Layer               │
│        (MySQL Database)             │
└─────────────────────────────────────┘
```

### Component Communication Flow
```
User Interface (Browser)
    ↕ (Livewire AJAX)
Livewire Volt Components
    ↕ (Service Injection)
Business Logic Services
    ↕ (Eloquent ORM)
Database Models
    ↕ (MySQL Driver)
MySQL Database
```

## Application Layers

### 1. Presentation Layer
**Location**: `resources/views/`

The presentation layer handles user interface and user experience through:

#### Livewire Volt Components
- **Landing Page**: `livewire/landing.blade.php` - Main date conversion interface with guest data migration
- **Modular Components**: `livewire/components/*.blade.php` - Extracted sections:
  - `calendar-input-section.blade.php` - Date input with validation, error handling, and date tracking
  - `calendar-quick-actions.blade.php` - Today/Yesterday/Tomorrow navigation
  - `calendar-results-grid.blade.php` - Main conversion display with immediate loading (all 19 calendars)
  - `calendar-search-filter.blade.php` - Calendar system filtering interface
  - `saved-conversions-section.blade.php` - User history management with database/localStorage integration
  - `saved-conversion-item.blade.php` - Individual saved conversion display with client-side rendering and table layout
  - `save-conversion-modal.blade.php` - Modal for saving conversions with optional notes (up to 2048 characters)
  - `edit-note-modal.blade.php` - Shared modal component for editing conversion notes
- **Authentication**: `livewire/auth/*.blade.php` - Login, register, password reset
- **Settings**: `livewire/settings/*.blade.php` - User profile and preferences
- **Dashboard**: `dashboard.blade.php` - User dashboard (traditional Blade)

#### Layout System
```
components/layouts/
├── app.blade.php           # Main authenticated application layout
├── auth.blade.php          # Authentication pages layout
├── public.blade.php        # Public pages layout (landing)
├── app/
│   ├── header.blade.php    # Application header with navigation
│   └── sidebar.blade.php   # Sidebar navigation
└── auth/
    ├── card.blade.php      # Card-style auth layout
    ├── simple.blade.php    # Simple auth layout
    └── split.blade.php     # Split-screen auth layout
```

#### Reusable Components
```
components/
├── app-logo.blade.php          # Application logo component
├── auth-header.blade.php       # Authentication header
├── input-otp.blade.php         # OTP input component
├── calendars/                  # Individual calendar display components (20 calendars)
│   ├── base-calendar-card.blade.php      # Base component template
│   ├── gregorian-card.blade.php          # Modern calendars
│   ├── jalali-card.blade.php
│   ├── hebrew-card.blade.php
│   ├── islamic-card.blade.php
│   ├── chinese-card.blade.php            # East Asian calendars
│   ├── japanese-card.blade.php
│   ├── coptic-card.blade.php             # Historical calendars
│   ├── ethiopian-card.blade.php
│   ├── egyptian-card.blade.php
│   ├── french-republican-card.blade.php
│   ├── babylonian-card.blade.php         # Ancient calendars
│   ├── assyrian-card.blade.php
│   ├── macedonian-card.blade.php
│   ├── mayan-card.blade.php
│   ├── iso8601-card.blade.php            # Technical formats
│   ├── julian-day-card.blade.php
│   ├── modified-julian-day-card.blade.php
│   └── unix-card.blade.php
├── loading-skeleton.blade.php   # Loading state component
└── settings/
    └── layout.blade.php        # Settings page layout
```

### 2. Exception Layer
**Location**: `app/Exceptions/`

Custom exception handling for calendar conversion operations:

#### Calendar Exception Hierarchy
```php
abstract class CalendarConversionException extends DateException
{
    // Base exception with calendar system context
    public function __construct(
        string $message = "",
        string $calendarSystem = 'unknown',
        ?array $dateComponents = null,
        int $code = 0,
        ?Throwable $previous = null
    );
}
```

**Specialized Exception Classes**:
- **CalendarMalformedDateException** extends `DateMalformedStringException`
  - Handles malformed date string input errors
  - Used when date strings cannot be parsed or are in invalid format
  
- **CalendarRangeException** extends `DateRangeError`
  - Handles date values outside valid calendar ranges
  - Used when dates fall outside supported calendar system boundaries
  
- **CalendarCalculationException** extends `CalendarConversionException`
  - Handles astronomical calculation failures and algorithm errors
  - Used for complex calendar computations (lunar calculations, astronomical data)
  
- **CalendarValidationException** extends `CalendarConversionException`
  - Handles date component validation failures
  - Used when individual date components (year, month, day) are invalid

**Features**:
- Calendar system context in error messages with `[{calendarSystem}]` prefix
- PSR-4 autoloading compliance with separate exception files
- Hierarchical structure for specific error handling
- Support for date component debugging information

### 3. Service Layer
**Location**: `app/Services/`

Business logic is encapsulated in service classes:

#### DateTimeConverterService
```php
class DateTimeConverterService
{
    public function convert(string $dateString): array
    {
        // Converts dates between 19 calendar systems
        // Returns: comprehensive conversion data with caching
        // Includes: gregorian, jalali, unix, iso8601, all supported calendars
    }
}
```

**Features**:
- Universal conversion between 19 calendar systems
- Input validation and error handling
- Multi-format date conversion with caching
- Calendar system categorization (modern, historical, ancient, technical)
- Lazy loading support for performance optimization
- Comprehensive output with Julian Day Number backbone

#### Test Runner Services
The application includes a comprehensive test execution system with specialized services:

**TestDiscoveryService**
```php
class TestDiscoveryService
{
    public function discoverTests(array $paths): array
    {
        // Automatically discovers test files across Unit, Feature, Browser suites
        // Returns: categorized test collections with metadata
        // Supports: PHPUnit and PestPHP test formats
    }
}
```

**TestExecutionService**
```php
class TestExecutionService
{
    public function executeInParallel(array $testSuites): void
    {
        // Manages parallel test execution with configurable process limits
        // Handles: process spawning, result collection, timeout management
        // Features: queue integration, resource monitoring, graceful shutdown
    }
}
```

**ProgressTrackingService**
```php
class ProgressTrackingService
{
    public function updateProgress(TestSuiteResult $result): void
    {
        // Maintains real-time execution statistics and visual progress bars
        // Provides: completion percentages, pass/fail counts, ETA calculations
        // Renders: ANSI-friendly progress displays for terminal interfaces
    }
}
```

**ErrorRecoveryService**
```php
class ErrorRecoveryService
{
    public function handleTestFailure(TestExecutionException $exception): RecoveryAction
    {
        // Diagnoses test failures and coordinates retry logic
        // Implements: exponential backoff, failure categorization, retry limits
        // Logs: contextual diagnostics and recovery attempt metadata
    }
}
```

**MarkdownReportService**
```php
class MarkdownReportService
{
    public function generateReport(array $testResults): string
    {
        // Generates comprehensive GitHub-flavored Markdown reports
        // Includes: execution summaries, failure analysis, performance metrics
        // Features: customizable templates, detailed test breakdowns
    }
}
```

### 4. Repository Layer
**Location**: `app/Models/`

Data access through Eloquent ORM:

#### User Model
```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use TwoFactorAuthenticatable; // Fortify trait
}
```

**Features**:
- Two-factor authentication support
- Email verification
- Standard Laravel authentication

### 5. Controller Layer
**Location**: `app/Http/Controllers/`

Minimal controller usage due to Livewire architecture:
- `Controller.php` - Base controller
- `Auth/VerifyEmailController.php` - Email verification

### 6. Provider Layer
**Location**: `app/Providers/`

Service providers for application configuration:
- `AppServiceProvider.php` - Application services
- `FortifyServiceProvider.php` - Authentication configuration
- `VoltServiceProvider.php` - Livewire Volt mounting

## TALL Stack Implementation

### Tailwind CSS 4 Integration
**Configuration**: `vite.config.js`
```javascript
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
```

**Content Scanning**: `resources/css/app.css`
```css
@source '../**/*.blade.php';
@source '../../app/Livewire/**/*.php';
@source '../../resources/views/**/*.blade.php';
```

### Alpine.js Integration
Alpine.js is automatically included with Livewire 3 and used for:
- Client-side state management
- UI interactivity (dropdowns, modals, toggles)
- Form enhancements
- Real-time updates with modern Livewire Volt `on()` event listeners
- Component communication between modular sections

**Modern Event Handling**:
```php
// New Livewire Volt on() helper for event listeners
on('converted', function ($data) {
    // Handle conversion results across components
});

// Alpine.js with entanglement
x-data="{ 
    showResults: @entangle('conversionResults').live,
    inputValue: @entangle('dateInput').live 
}"
```

### Livewire 3 with Volt
**Configuration**: `app/Providers/VoltServiceProvider.php`
```php
Volt::mount([
    config('livewire.view_path', resource_path('views/livewire')),
    resource_path('views/pages'),
]);
```

**Component Structure**:
```php
<?php
use function Livewire\Volt\{state, mount, layout, rules};

// Component logic
state('property', 'default');
rules(['property' => 'validation']);

$action = function () {
    // Action logic
};
?>

<!-- Blade template -->
<div>Component HTML</div>
```

### Laravel 12 Features
- Modern PHP 8.2+ syntax
- Improved performance
- Enhanced security features
- Better testing capabilities

## Authentication Architecture

### Multi-Provider Authentication System

EpochBridge implements a comprehensive authentication system supporting both traditional and OAuth-based authentication:

#### Laravel Fortify Integration
**Configuration**: `config/fortify.php`

Fortify provides traditional authentication:
- User registration with email/password
- Login/logout functionality
- Password reset workflow
- Email verification
- Two-factor authentication
- Password confirmation for sensitive operations

#### Laravel Socialite Integration
**Configuration**: `config/services.php`

Socialite provides OAuth 2.0 authentication:
- **Google Sign-In**: OAuth 2.0 integration with Google
- **Account Linking**: Associate OAuth accounts with existing users
- **Token Management**: Encrypted storage of access/refresh tokens
- **Auto-verification**: OAuth users skip email verification

**OAuth Configuration**:
```php
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI', '/auth/google/callback'),
]
```

### Authentication Flow

```
1. Traditional Registration
   ├── Email verification required
   ├── Password validation (bcrypt hashing)
   └── Automatic login after verification

2. OAuth Authentication (Google)
   ├── Redirect to OAuth provider
   ├── User consent and authorization
   ├── Callback with authorization code
   ├── Token exchange (access + refresh tokens)
   ├── User profile retrieval
   ├── Account creation or linking
   └── Automatic login (email pre-verified)

3. Account Linking
   ├── Existing user + new OAuth provider
   ├── Multiple OAuth providers per user
   └── Social account relationship management

4. Two-Factor Authentication
   ├── TOTP (Time-based One-Time Password)
   ├── Recovery codes generation
   └── QR code setup

5. Session Management
   ├── Web guard authentication
   ├── Session-based storage
   └── Remember me functionality
```

### Authentication Components

#### Livewire Volt Components
```
livewire/auth/
├── login.blade.php              # Login form with OAuth buttons
├── register.blade.php           # Registration form
├── forgot-password.blade.php    # Password reset request
├── reset-password.blade.php     # Password reset form
├── verify-email.blade.php       # Email verification
├── confirm-password.blade.php   # Password confirmation
└── two-factor-challenge.blade.php # 2FA challenge
```

#### OAuth Controllers
```
app/Http/Controllers/Auth/
└── SocialAuthController.php     # OAuth provider integration
    ├── redirectToProvider()     # Initiates OAuth flow
    ├── handleProviderCallback() # Processes OAuth callback
    ├── findOrCreateUser()       # User lookup/creation logic
    └── validateProvider()       # Provider whitelist validation
```

#### OAuth Routes
```php
// OAuth authentication routes
Route::prefix('auth')->group(function () {
    Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])
        ->name('social.redirect')
        ->where('provider', 'google');

    Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
        ->name('social.callback')
        ->where('provider', 'google');
});
```

### Data Models

#### User Model Extensions
```php
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use TwoFactorAuthenticatable;

    // OAuth relationships
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    // Helper methods
    public function hasLinkedProvider(string $provider): bool;
    public function getLinkedProvider(string $provider): ?SocialAccount;
}
```

**Features**:
- Nullable password field (supports OAuth-only accounts)
- Avatar URL storage from OAuth providers
- Email verification auto-set for OAuth users

#### SocialAccount Model
```php
class SocialAccount extends Model
{
    protected $fillable = [
        'user_id', 'provider', 'provider_id',
        'access_token', 'refresh_token', 'expires_in',
        'avatar', 'nickname'
    ];

    protected $casts = [
        'access_token' => 'encrypted',    // Encrypted at rest
        'refresh_token' => 'encrypted',   // Encrypted at rest
        'expires_in' => 'integer',
    ];

    public function user(): BelongsTo;
    public function isTokenExpired(): bool;
}
```

**Security Features**:
- Encrypted token storage using Laravel's encrypted casting
- Hidden tokens in JSON serialization
- Token expiration detection

### Authorization Structure
- **Middleware**: `auth`, `verified`, `password.confirm`, `guest`
- **Guards**: `web` (default)
- **Route Protection**: Applied at route level

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Protected routes requiring authentication
});

Route::middleware('guest')->group(function () {
    // Public routes (login, register, OAuth)
});
```

### OAuth Security Considerations

1. **Provider Validation**: Whitelist-based provider checking
2. **State Parameter**: CSRF protection via Socialite's state mechanism
3. **Token Encryption**: All OAuth tokens encrypted at database level
4. **Email Validation**: Required from OAuth provider
5. **Transaction Safety**: Database transactions for user creation
6. **Error Logging**: Comprehensive logging without exposing tokens
7. **IP Tracking**: OAuth attempt logging with IP addresses

## Calendar System Integration

### Multi-Calendar Support Architecture

#### Primary Libraries
1. **fisharebest/ext-calendar**: Extended calendar functionality
   - Julian Day Number conversions
   - Hebrew, Islamic (Hijri), French Republican calendars
   - Historical date accuracy with astronomical calculations

2. **morilog/jalali**: Persian/Jalali calendar
   - Jalali to Gregorian conversion
   - Persian month/day names
   - Cultural date formatting

#### Calendar Service Architecture (15+ Calendars)
```
app/Services/
├── DateTimeConverterService.php    # Main orchestrator
├── CalendarConversionService.php   # Base conversion utilities
├── JulianDayService.php           # Universal Julian Day Number conversions (astronomical)
├── JulianCalendarService.php      # Julian calendar (historical dates)
├── HebrewCalendarService.php      # Jewish calendar (lunar-solar)
├── IslamicCalendarService.php     # Hijri lunar calendar
├── ChineseCalendarService.php     # Chinese lunisolar calendar (orchestrator)
├── Chinese/                       # Chinese calendar astronomical subsystem
│   ├── ChineseLunisolarCalendar.php  # Suì construction and lunation logic
│   ├── AstronomyCalculator.php       # Meeus-based Sun/Moon ephemerides with ΔT
│   └── LunarMonth.php                # Lunation data structure with month length
├── JapaneseCalendarService.php    # Imperial era-based calendar
├── FrenchRepublicanService.php    # Revolutionary calendar with DI & proleptic support
├── CopticCalendarService.php      # Egyptian Christian calendar
├── EthiopianCalendarService.php   # Traditional Ethiopian calendar
├── EgyptianCalendarService.php    # Ancient Egyptian civil calendar
├── BabylonianCalendarService.php  # Ancient Mesopotamian lunar
├── AssyrianCalendarService.php    # Ancient Assyrian calendar
├── AncientMacedonianService.php   # Ancient Macedonian calendar
├── MayanCalendarService.php       # Complex Mesoamerican system
└── AncientAstronomyService.php    # Astronomical calculations

app/Contracts/Calendar/
└── FrenchRepublicanCalendarAdapter.php # Adapter interface for FR calendar

app/Support/Calendars/
└── FrenchRepublicanExtCalendarAdapter.php # Concrete FR adapter implementation
```

#### Universal Conversion Process Flow
```
User Input (Date in any format)
    ↓
DateTimeConverterService::convert()
    ↓
Input validation & normalization
    ↓
Julian Day Number calculation (universal backbone)
    ↓
Parallel conversion to all 19 calendar systems (15+ calendars + technical formats):
├── Modern Calendars (Gregorian, Persian, Hebrew, Islamic)
├── Historical Calendars (Julian calendar, French Republican, Coptic, Ethiopian, Egyptian)
├── Ancient Calendars (Babylonian, Assyrian, Ancient Macedonian, Mayan)
└── East Asian Calendars (Chinese Lunisolar, Japanese Era-based)
    ↓
Comprehensive output with:
├── Native date components (year, month, day)
├── Cultural names in original scripts
├── English transliterations
├── Historical context and era information
├── Julian Day Numbers (astronomical reference)
├── Unix timestamps
└── ISO 8601 formatting

Note: Julian calendar and Julian Day Numbers are separate concepts:
- Julian calendar: Historical calendar system (date representation)
- Julian Day Number: Astronomical day count used as conversion backbone
```

#### Calendar Data Models
```
app/Models/
├── User.php                      # User with saved conversions
├── SavedConversion.php          # User conversion history
├── JapaneseEra.php             # Japanese imperial eras (Heisei, Reiwa, etc.)
├── HistoricalCalendarData.php  # Ancient calendar reference data
└── MayanCalendarData.php       # Mayan calendar calculations

database/migrations/
├── *_create_saved_conversions_table.php
├── *_create_japanese_eras_table.php
├── *_create_historical_calendar_data_table.php
└── *_create_mayan_calendar_data_table.php

config/calendars.php
└── french_republican            # Configuration for French Republican calendar
    ├── apply_paris_offset: true  # Apply Paris mean solar offset (UT1 + 00:09:21)
    └── allow_proleptic: true     # Extend calendar beyond historical 1792-1805 range
```

### Adapter Pattern for Calendar Services

The French Republican calendar implementation uses the **Adapter Pattern** with **Dependency Injection** to enable flexible calendar calculation strategies and improve testability.

#### Architecture Components

**1. Adapter Interface**
```php
// app/Contracts/Calendar/FrenchRepublicanCalendarAdapter.php
interface FrenchRepublicanCalendarAdapter
{
    public function jdToYmd(int $julianDay): array;  // Convert JD to year/month/day
    public function ymdToJd(int $year, int $month, int $day): int;  // Convert date to JD
    public function jdStart(): int;  // Calendar epoch start
    public function jdEnd(): int;    // Calendar epoch end
    public function isLeapYear(int $year): bool;  // Leap year calculation
}
```

**2. Concrete Implementation**
```php
// app/Support/Calendars/FrenchRepublicanExtCalendarAdapter.php
class FrenchRepublicanExtCalendarAdapter implements FrenchRepublicanCalendarAdapter
{
    // Wraps fisharebest/ext-calendar FrenchCalendar class
    // Implements caching for repeated conversions
    // Applies Paris mean solar offset (UT1 + 00:09:21) for historical accuracy
}
```

**3. Service with Dependency Injection**
```php
// app/Services/FrenchRepublicanService.php
class FrenchRepublicanService
{
    public function __construct(
        private FrenchRepublicanCalendarAdapter $adapter
    ) {}
    
    public function fromGregorian(Carbon $date): array
    {
        // Uses injected adapter for conversions
        // Supports proleptic extension beyond 1792-1805
        // Configurable Paris offset application
    }
}
```

**4. Service Provider Binding**
```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->bind(
        FrenchRepublicanCalendarAdapter::class,
        FrenchRepublicanExtCalendarAdapter::class
    );
}
```

#### Benefits of Adapter Pattern

- **Testability**: Easy to mock adapter for unit tests
- **Flexibility**: Swap calculation strategies (astronomical vs arithmetic)
- **Maintainability**: Decoupled from specific library implementation
- **Performance**: Per-instance memoization/caching without global state
- **Extensibility**: Add alternative adapters without modifying service code

#### Configuration Options

French Republican calendar behavior is configurable via `config/calendars.php`:

```php
'french_republican' => [
    // Apply Paris mean solar offset (UT1 + 00:09:21) for historical accuracy
    'apply_paris_offset' => env('FRENCH_REPUBLICAN_PARIS_OFFSET', true),
    
    // Allow proleptic extension beyond historical 1792-1805 range
    'allow_proleptic' => env('FRENCH_REPUBLICAN_ALLOW_PROLEPTIC', true),
],
```

**Paris Mean Solar Offset**: The French Republican calendar was based on astronomical observations at the Paris Observatory. This offset aligns conversions with historical equinox observations.

**Proleptic Support**: Extends calendar calculations beyond the official usage period (1792-1805) using mathematical rules, allowing conversion of any date.

### Chinese Calendar Astronomical Architecture

The Chinese lunisolar calendar implementation uses a sophisticated multi-class architecture for precise astronomical calculations:

#### Components

**ChineseLunisolarCalendar** (`app/Services/Chinese/ChineseLunisolarCalendar.php`)
- Orchestrates suì (Chinese year) construction and lunation sequencing
- Implements dìngqì solar term convention for accurate leap month detection
- Caches lunation sequences per suì and per Chinese year for performance
- Uses "first lunation after winter solstice + one" rule for 正月 (first month)
- Handles leap month detection via "first month with no major term" rule

**AstronomyCalculator** (`app/Services/Chinese/AstronomyCalculator.php`)
- Provides Meeus-based Sun and Moon ephemerides (Jean Meeus algorithms)
- Implements ΔT (Delta T) corrections using Espenak–Meeus polynomials for 1900–2100
- Calculates new moon times from Meeus chapter 49 with all periodic corrections
- Solves major solar terms via Newton iteration on apparent solar longitude
- Converts astronomical times to Beijing civil days (UTC+8) with midnight boundaries

**LunarMonth** (`app/Services/Chinese/LunarMonth.php`)
- Immutable data structure representing a single lunation (lunar month)
- Contains: Chinese year, month number, leap flag, month length, start/end dates
- Provides helper methods for month length calculation and date validation

#### Features

- **Astronomical Accuracy**: True lunisolar calculations using Jean Meeus algorithms
- **ΔT Corrections**: Applies proper time corrections for historical accuracy
- **Leap Month Detection**: Uses dìngqì convention (first post-solstice lunation lacking major term)
- **Performance Caching**: Month sequences cached per suì to avoid redundant calculations
- **Edge Case Handling**: Properly handles anomalies like the 2033 leap month 11
- **Validation & Conversion**: Self-consistent month lengths from computed lunations
- **Solar Terms**: All 24 solar terms calculated from apparent solar longitude (15° bins)

#### Configuration

Chinese calendar calculations use Beijing timezone (`Asia/Shanghai`, UTC+8) for all astronomical events and civil day boundaries.

## Database Architecture

### Database Configuration
**Environment**: Docker-based MySQL 8.0
**Ports**: 
- Development: 3307
- Testing: Separate `testing` database

### Migration Structure
```
database/migrations/
├── 0001_01_01_000000_create_users_table.php
├── 0001_01_01_000001_create_cache_table.php
├── 0001_01_01_000002_create_jobs_table.php
├── 2025_01_15_000000_create_japanese_eras_table.php
├── 2025_01_16_000000_create_historical_calendar_data_table.php
├── 2025_09_02_075243_add_two_factor_columns_to_users_table.php
├── 2025_10_07_221538_create_saved_conversions_table.php
├── 2025_10_24_204329_create_social_accounts_table.php
├── 2025_10_24_204408_modify_users_table_for_social_auth.php
├── 2025_11_10_000000_create_mayan_calendar_data_table.php
└── 2026_01_07_000000_create_file_table.php
```

### Schema Design

#### Users Table
```sql
users
├── id (bigint, primary key)
├── name (varchar)
├── email (varchar, unique)
├── email_verified_at (timestamp, nullable)
├── password (varchar, nullable)              # Nullable for OAuth-only accounts
├── avatar (varchar, nullable)                # Profile avatar from OAuth
├── remember_token (varchar, nullable)
├── two_factor_secret (text, nullable)        # 2FA secret
├── two_factor_recovery_codes (text, nullable) # Recovery codes
├── two_factor_confirmed_at (timestamp, nullable) # 2FA confirmation
├── created_at (timestamp)
└── updated_at (timestamp)
```

**OAuth Extensions**:
- Password is now nullable to support OAuth-only accounts
- Avatar field stores profile image URL from OAuth providers
- Email is auto-verified for OAuth users

#### Social Accounts Table
```sql
social_accounts
├── id (bigint, primary key)
├── user_id (bigint, foreign key to users.id, on delete cascade)
├── provider (varchar)                        # OAuth provider (e.g., 'google')
├── provider_id (varchar)                     # Unique ID from provider
├── access_token (text, encrypted)            # Encrypted OAuth access token
├── refresh_token (text, nullable, encrypted) # Encrypted OAuth refresh token
├── expires_in (integer, nullable)            # Token expiration time in seconds
├── avatar (varchar, nullable)                # Provider avatar URL
├── nickname (varchar, nullable)              # Provider nickname
├── created_at (timestamp)
└── updated_at (timestamp)

Indexes:
├── UNIQUE INDEX on (provider, provider_id)   # One provider account per user
└── INDEX on user_id                          # Fast user lookup
```

**Security Features**:
- Access and refresh tokens encrypted using Laravel's encrypted casting
- Unique constraint prevents duplicate provider accounts
- Cascade delete ensures cleanup when user is deleted
- Token expiration tracking for refresh logic

#### Saved Conversions Table
```sql
saved_conversions
├── id (bigint, primary key)
├── user_id (bigint, foreign key to users.id, nullable) # Null for guest conversions
├── conversion_data (json)                              # Complete conversion results (all 19 calendars)
├── note (text, nullable)                               # Optional user note (up to 2048 chars)
├── input_date (varchar)                               # Original input date for tracking
├── created_at (timestamp)                             # Conversion save timestamp
├── updated_at (timestamp)
```

**Features**:
- Guest conversions stored with `user_id = null` for guest users
- Complete calendar data in JSON format including all 19 calendar systems
- Optional note field for user annotations (2048 character limit)
- Input date tracking for save state management
- Automatic migration from localStorage to database on user authentication
- Supports both authenticated and guest user workflows with notes
- Notes persisted in guest localStorage and migrated on authentication

#### On This Day Cache Table (Planned)
```sql
file
├── id (bigint, primary key)
├── language (varchar)                        # Default 'en'
├── type (varchar)                            # Default 'all'
├── month (tinyint)                           # 1-12
├── day (tinyint)                             # 1-31
├── day_info (varchar)                        # Raw Wikimedia response (stored as a JSON string)
├── fetched_at (timestamp)                    # API fetch timestamp
├── created_at (timestamp)
└── updated_at (timestamp)

Indexes:
└── UNIQUE INDEX on (language, type, month, day)
```

**Features**:
- Cache-first lookups for Wikimedia \"On this day\" responses
- Prevents redundant API requests for previously fetched dates

#### Japanese Eras Table
```sql
japanese_eras
├── id (bigint, primary key)
├── name_japanese (varchar)    # Era name in Japanese
├── name_english (varchar)     # Era name in English
├── start_date (date)          # Era start date (Gregorian)
├── end_date (date, nullable)  # Era end date (null for current era)
├── created_at (timestamp)
└── updated_at (timestamp)
```

#### Historical Calendar Data Table
```sql
historical_calendar_data
├── id (bigint, primary key)
├── calendar_type (varchar)    # Calendar system identifier
├── data_type (varchar)        # Type of data (month_names, era_info, etc.)
├── data_content (json)        # Flexible JSON storage for calendar data
├── metadata (json, nullable)  # Additional metadata
├── created_at (timestamp)
└── updated_at (timestamp)
```

#### Mayan Calendar Data Table
```sql
mayan_calendar_data
├── id (bigint, primary key)
├── long_count (varchar)       # Mayan Long Count date
├── haab_day (integer)         # Haab calendar day
├── haab_month (varchar)       # Haab calendar month
├── tzolkin_day (integer)      # Tzolkin calendar day
├── tzolkin_sign (varchar)     # Tzolkin calendar sign
├── gregorian_date (date)      # Corresponding Gregorian date
├── julian_day_number (integer) # Julian Day Number
├── notes (text, nullable)     # Additional notes
├── created_at (timestamp)
└── updated_at (timestamp)
```

#### System Tables
- **cache**: Application caching
- **cache_locks**: Cache locking mechanism
- **jobs**: Queue system
- **job_batches**: Batch job tracking
- **failed_jobs**: Failed job tracking
- **password_reset_tokens**: Password reset tokens
- **sessions**: User session storage

### Factory Pattern
```php
// database/factories/UserFactory.php
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
```

## Frontend Architecture

### Asset Pipeline
**Build Tool**: Vite with Laravel integration
**Configuration**: `vite.config.js`

#### Asset Structure
```
resources/
├── css/
│   └── app.css              # Main CSS with Tailwind directives
├── js/
│   └── app.js               # Main JavaScript entry point
└── views/                   # Blade templates
```

#### Build Process
```
Development:
npm run dev → Vite dev server (port 5174) → HMR updates

Production:
npm run build → Optimized bundles → public/build/
```

### Component Architecture

#### Flux UI Integration
Pre-built components from Flux UI library:
```
resources/views/flux/
├── icon/
│   ├── book-open-text.blade.php
│   ├── chevrons-up-down.blade.php
│   ├── folder-git-2.blade.php
│   └── layout-grid.blade.php
└── navlist/
    └── group.blade.php
```

#### Responsive Design Strategy
- **Mobile-first approach** with Tailwind CSS
- **Breakpoint system**: `sm:`, `md:`, `lg:`, `xl:`, `2xl:`
- **Adaptive layouts** for different screen sizes
- **Touch-friendly interfaces** for mobile devices

### State Management
**Client-side**: Alpine.js reactive data with method invocation
**Server-side**: Livewire component state
**Synchronization**: Livewire entanglement and event listeners
**Guest Storage**: Browser localStorage for unauthenticated users

```javascript
// Alpine.js state management with proper method invocation
x-data="{
    showResults: @entangle('conversionResults').live,
    inputValue: @entangle('dateInput').live,
    savedConversions: [],
    
    // Guest conversion management
    loadGuestConversions() {
        const stored = localStorage.getItem('guestConversions');
        this.savedConversions = stored ? JSON.parse(stored) : [];
    },
    
    saveGuestConversion(data) {
        this.savedConversions.unshift(data);
        localStorage.setItem('guestConversions', JSON.stringify(this.savedConversions));
    }
}"

// Livewire event handling
on('converted', function ($data) {
    // Handle conversion results and update save state
});
```

### Guest Data Migration Architecture

EpochBridge implements a seamless guest-to-authenticated user data migration system:

**Guest User Flow**:
1. Conversions saved to browser localStorage
2. Client-side rendering via Alpine.js
3. Full 19-calendar data stored in JSON format
4. Notes stored alongside conversion data
5. Persistent across browser sessions

**Authentication Migration**:
1. On user login/registration, guest data detected in localStorage
2. Livewire component automatically migrates conversions to database
3. `user_id` field populated with authenticated user ID
4. Notes migrated along with conversion data
5. localStorage cleared after successful migration
6. User sees complete conversion history with notes immediately

**Implementation Details**:
```php
// Landing page component - guest data migration with notes
public function migrateGuestData(array $guestConversions): void
{
    foreach ($guestConversions as $conversion) {
        SavedConversion::create([
            'user_id' => auth()->id(),
            'conversion_data' => $conversion['conversion_data'],
            'note' => $conversion['note'] ?? null,
            'input_date' => $conversion['input_date'],
            'created_at' => $conversion['created_at'] ?? now(),
        ]);
    }
}
```

**Benefits**:
- No data loss when guests create accounts (including notes)
- Transparent user experience
- Encourages user registration
- Maintains data integrity
- Preserves user annotations across authentication

## Testing Architecture

### Testing Framework Stack
- **Primary**: PestPHP 4.1+ (PHP testing framework)
- **Laravel Integration**: PestPHP Laravel plugin
- **Browser Testing**: Laravel Dusk 8.2+
- **Test Database**: Separate `testing` database
- **Interactive Test Runner**: Custom parallel execution system with real-time reporting

### Test Runner System Architecture

The project features a comprehensive test runner system with the following components:

#### Core Services
- **TestDiscoveryService**: Automatically discovers and categorizes tests across Unit, Feature, and Browser suites
- **TestExecutionService**: Manages parallel test execution with configurable process limits
- **ProgressTrackingService**: Provides real-time progress updates with visual progress bars
- **ErrorRecoveryService**: Implements retry logic with exponential backoff for robust execution
- **MarkdownReportService**: Generates detailed test reports in GitHub-flavored Markdown format

#### Command Interface
- **InteractiveTestRunnerCommand**: `php artisan test:runner` - Interactive menu system for test execution
- **Configuration**: Environment-specific settings via `config/test-runner.php`
- **Background Processing**: Queue-based test execution via `ExecuteTestSuiteJob`

#### Event System
- **TestExecutionStarted**: Fired when test suite begins execution
- **TestCompleted**: Individual test completion events
- **TestExecutionCompleted**: Suite completion with results summary
- **Real-time Updates**: Event listeners update progress displays and reports

### Test Structure
```
tests/
├── Pest.php                    # PestPHP configuration
├── TestCase.php                # Base test case
├── DuskTestCase.php            # Dusk browser test base
├── Feature/                    # Feature tests
│   ├── Auth/                   # Authentication tests
│   │   ├── AuthenticationTest.php           # Login/logout functionality
│   │   ├── EmailVerificationTest.php        # Email verification flow
│   │   ├── PasswordConfirmationTest.php     # Password confirmation
│   │   ├── PasswordResetTest.php            # Password reset functionality
│   │   ├── RegistrationTest.php             # User registration
│   │   └── TwoFactorChallengeTest.php       # 2FA challenge flow
│   ├── Console/                # Console command tests
│   │   └── InteractiveTestRunnerCommandTest.php # Test runner command tests
│   ├── Integration/            # Integration workflow tests
│   │   └── FullTestRunnerWorkflowTest.php       # End-to-end test runner tests
│   ├── Jobs/                   # Background job tests
│   │   └── ExecuteTestSuiteJobTest.php         # Test suite execution job tests
│   ├── Settings/               # Settings functionality tests
│   │   ├── PasswordUpdateTest.php           # Password change
│   │   ├── ProfileUpdateTest.php            # Profile modification
│   │   └── TwoFactorAuthenticationTest.php  # 2FA setup/management
│   ├── Livewire/               # Livewire component tests
│   │   ├── SaveConversionWithNoteTest.php   # Save operations with notes
│   │   ├── UpdateConversionNoteTest.php     # Note update workflows
│   │   ├── Components/         # Modular component tests
│   │   │   ├── CalendarQuickActionsTest.php     # Quick action buttons
│   │   │   ├── CalendarResultsGridTest.php      # Conversion display grid
│   │   │   ├── CalendarSearchFilterTest.php     # Calendar filtering
│   │   │   ├── SavedConversionsSectionTest.php  # Saved conversions
│   │   │   ├── EditNoteModalTest.php            # Note editing modal
│   │   │   └── SaveNoteUpdateEventTest.php      # Note event handling
│   │   └── LandingPageTest.php # Main landing page component testing
│   ├── DashboardTest.php       # Dashboard tests
│   └── ExampleTest.php         # Example feature test
├── Unit/                       # Unit tests
│   ├── Models/                 # Model tests
│   │   └── SavedConversionNoteTest.php      # Note field behavior tests
│   ├── Services/               # Service layer tests
│   │   ├── Calendar Services/  # Calendar conversion service tests
│   │   │   ├── ChineseCalendarServiceTest.php   # Chinese calendar service
│   │   │   ├── CopticCalendarServiceTest.php    # Coptic calendar service
│   │   │   ├── DateTimeConverterServiceTest.php # Main converter service
│   │   │   ├── EgyptianCalendarServiceTest.php  # Egyptian calendar service
│   │   │   ├── EthiopianCalendarServiceTest.php # Ethiopian calendar service
│   │   │   └── JapaneseCalendarServiceTest.php  # Japanese calendar service
│   │   └── Test Runner Services/ # Test runner service tests
│   │       ├── ErrorRecoveryServiceTest.php    # Error recovery logic tests
│   │       ├── MarkdownReportServiceTest.php   # Report generation tests
│   │       ├── ProgressTrackingServiceTest.php # Progress tracking tests
│   │       └── TestDiscoveryServiceTest.php    # Test discovery tests
│   └── ExampleTest.php         # Example unit test
├── Support/                    # Test support utilities
│   └── PromptSequence.php      # Interactive command testing utilities
└── Browser/                    # Dusk browser tests
    ├── ConversionNoteManagementTest.php # Complete note management workflows
    ├── Components/             # Component-specific browser tests
    ├── Pages/                  # Page object models
    │   ├── HomePage.php        # Home page object model
    │   └── Page.php           # Base page object model
    ├── console/                # Browser test console logs
    ├── screenshots/            # Test failure screenshots
    ├── source/                 # HTML source dumps
    ├── ExampleTest.php         # Example browser test
    └── LandingPageTest.php     # Full page interaction tests
```

### Testing Strategy

#### Unit Tests
Focus on isolated component testing:
```php
// tests/Unit/Services/DateTimeConverterServiceTest.php
test('converts valid date correctly', function () {
    $service = new DateTimeConverterService();
    $result = $service->convert('2024-01-01');
    
    expect($result)->toHaveKeys(['gregorian', 'jalali', 'unix', 'iso8601']);
});
```

#### Feature Tests
Test complete user workflows:
```php
// tests/Feature/Livewire/LandingPageTest.php
test('can convert date through landing page', function () {
    Livewire::test('landing')
        ->set('dateInput', '2024-01-01')
        ->call('convertDate')
        ->assertSet('conversionResults.gregorian.full_date', '2024-01-01');
});
```

#### Browser Tests
End-to-end user experience testing:
```php
// tests/Browser/ExampleTest.php
public function test_landing_page_conversion()
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->type('dateInput', '2024-01-01')
            ->click('@convert-button')
            ->waitForText('Gregorian');
    });
}
```

### Test Configuration
**PHPUnit Configuration**: `phpunit.xml`
- Separate testing environment
- Database transactions for isolation
- Specific test directories

## Build & Deployment Architecture

### Development Environment
**Docker-based development** with Laravel Sail:

```yaml
# docker-compose.yml (via Sail)
services:
    laravel.test:
        ports:
            - '8081:80'      # Laravel application
    mysql:
        ports:
            - '3307:3306'    # MySQL database
    redis:
        ports:
            - '6380:6379'    # Redis cache
```

### Build Process

#### Frontend Build Pipeline
```bash
# Development
npm run dev          # Start Vite dev server with HMR

# Production
npm run build        # Build optimized assets
```

#### Backend Optimization
```bash
# Production optimization
php artisan optimize      # Optimize framework
php artisan config:cache  # Cache configuration
php artisan route:cache   # Cache routes
php artisan view:cache    # Cache views
```

### Deployment Architecture

#### Production Checklist
1. **Environment Configuration**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Asset Compilation**
   ```bash
   npm run build
   ```

3. **Laravel Optimization**
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan optimize
   ```

#### Server Requirements
- **PHP**: 8.2+
- **Database**: MySQL 8.0+
- **Web Server**: Nginx/Apache with PHP-FPM
- **Process Manager**: Supervisor (for queues)

### Production Docker Deployment

EpochBridge includes a production-ready Docker setup for containerized deployment.

#### Docker Architecture
```
┌─────────────────────────────────────────────────────────┐
│                    Docker Container                      │
├─────────────────────────────────────────────────────────┤
│                     Supervisord                          │
│              (Process Manager - PID 1)                   │
├─────────────────────┬───────────────────────────────────┤
│        Nginx        │           PHP-FPM                  │
│    (Port 80/443)    │      (FastCGI Socket)             │
│   - Static assets   │   - Laravel application            │
│   - Reverse proxy   │   - PHP 8.2                       │
├─────────────────────┴───────────────────────────────────┤
│              Laravel Application Code                    │
│         /var/www/html (Optimized Production)            │
└─────────────────────────────────────────────────────────┘
```

#### Multi-Stage Build Process
The Dockerfile uses three stages for optimized builds:

1. **Composer Builder Stage**
   - Installs PHP dependencies without dev packages
   - Generates optimized autoloader
   - Cached layer for faster rebuilds

2. **Frontend Builder Stage**
   - Node.js 22 Alpine environment
   - Builds production-optimized Vite assets
   - Separate layer prevents rebuilds when PHP changes

3. **Production Image Stage**
   - PHP 8.2-FPM on Alpine Linux (minimal footprint)
   - Nginx for reverse proxy and static assets
   - Supervisord for process management
   - Copies optimized artifacts from builder stages

#### Docker Files
```
.
├── Dockerfile              # Multi-stage production build
├── .dockerignore           # Build context exclusions
└── docker/
    ├── nginx.conf          # Nginx site configuration
    ├── supervisord.conf    # Process supervisor config
    └── entrypoint.sh       # Container startup script
```

#### Deployment Commands
```bash
# Build production image
docker build -t epochbridge:latest .

# Run container with environment variables
docker run -d \
  --name epochbridge \
  -p 80:80 \
  -e APP_KEY=base64:your-key \
  -e DB_HOST=mysql-host \
  -e DB_DATABASE=epochbridge \
  -e DB_USERNAME=user \
  -e DB_PASSWORD=password \
  epochbridge:latest

# View container logs
docker logs -f epochbridge

# Execute artisan commands
docker exec epochbridge php artisan migrate --force
```

## Security Architecture

### Authentication Security
- **Password Hashing**: Bcrypt/Argon2
- **Session Security**: Secure session configuration
- **CSRF Protection**: Laravel's built-in CSRF middleware
- **Two-Factor Authentication**: TOTP with recovery codes

### Data Protection
- **Input Validation**: Laravel validation rules
- **SQL Injection Protection**: Eloquent ORM parameterized queries
- **XSS Protection**: Blade template escaping
- **Mass Assignment Protection**: Fillable properties

### Security Headers
```php
// Middleware configuration
'headers' => [
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000',
]
```

### Environment Security
- **Environment Variables**: Sensitive data in `.env`
- **Key Management**: Application key rotation
- **Database Security**: Separate user accounts with minimal privileges

## Performance Considerations

### Frontend Performance
- **Asset Optimization**: Vite production builds
- **CSS Optimization**: Tailwind CSS purging
- **JavaScript Optimization**: Tree shaking and minification
- **Lazy Loading**: Livewire lazy components

### Backend Performance
- **Database Optimization**: Query optimization with Eloquent
- **Caching Strategy**: Redis/file-based caching
- **Session Storage**: Database/Redis session storage
- **Queue System**: Background job processing

### Monitoring & Optimization
- **Query Monitoring**: Laravel Debugbar in development
- **Log Management**: Laravel Pail for real-time log viewing
- **Performance Profiling**: Laravel Telescope (can be added)

### Scalability Considerations
- **Horizontal Scaling**: Stateless application design
- **Database Scaling**: Read replicas support
- **CDN Integration**: Static asset delivery
- **Queue Workers**: Background processing scalability

---

## Architecture Decision Records (ADRs)

### ADR-001: TALL Stack Selection
**Decision**: Use TALL Stack (Tailwind, Alpine.js, Livewire, Laravel)
**Rationale**: 
- Unified development experience
- Minimal JavaScript complexity
- Rapid prototyping capabilities
- Strong Laravel ecosystem integration

### ADR-002: Livewire Volt Adoption
**Decision**: Use Livewire Volt for single-file components
**Rationale**:
- Reduced file complexity
- Faster development cycle
- Cleaner component organization
- Better developer experience

### ADR-003: Calendar Library Selection
**Decision**: Combine `fisharebest/ext-calendar` and `morilog/jalali`
**Rationale**:
- Comprehensive calendar system support
- Accurate historical date calculations
- Active community maintenance
- Laravel ecosystem compatibility

### ADR-005: Adapter Pattern for Calendar Services
**Decision**: Use adapter pattern with dependency injection for complex calendar implementations
**Rationale**:
- Improved testability with mockable interfaces
- Flexibility to swap calculation strategies (astronomical vs arithmetic)
- Decoupling from specific library implementations
- Per-instance caching without global state
- Extensibility for alternative calendar calculation methods
**Initial Implementation**: French Republican calendar service
**Future Applicability**: Can extend to other complex calendars (Chinese, Islamic, Hebrew)

### ADR-004: Docker Development Environment
**Decision**: Use Laravel Sail for development
**Rationale**:
- Consistent development environment
- Easy onboarding for new developers
- Isolated service dependencies
- Production-like environment

---

This architecture overview provides a comprehensive understanding of EpochBridge's technical implementation, design decisions, and operational considerations. The architecture supports scalability, maintainability, and developer productivity while delivering a robust date conversion platform.
