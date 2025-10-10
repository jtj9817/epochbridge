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

EpochBridge is a comprehensive calendar conversion platform built using the TALL Stack (Tailwind CSS, Alpine.js, Livewire, Laravel) architecture. The application supports 15+ historical and modern calendar systems with complete user management and conversion history features.

### Core Purpose
- **Primary Function**: Universal calendar conversion between 15+ calendar systems including modern, historical, and ancient calendars
- **Target Users**: Historians, developers, researchers, genealogists, and anyone working with diverse calendar systems
- **Key Features**: 
  - Real-time conversion between 15+ calendar systems
  - Historical accuracy with astronomical calculations
  - User conversion management and history
  - Clipboard integration for easy data export
  - Comprehensive authentication and user profiles

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
- **Containerization**: Laravel Sail 1.41+ (Docker)
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
- **Landing Page**: `livewire/landing.blade.php` - Main date conversion interface
- **Modular Components**: `livewire/components/*.blade.php` - Extracted sections:
  - `calendar-input-section.blade.php` - Date input with validation and error handling
  - `calendar-quick-actions.blade.php` - Today/Yesterday/Tomorrow navigation
  - `calendar-results-grid.blade.php` - Main conversion display with lazy loading
  - `calendar-search-filter.blade.php` - Calendar system filtering interface
  - `saved-conversions-section.blade.php` - User history management
  - `saved-conversion-item.blade.php` - Individual saved conversion display
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
        // Converts dates between 15+ calendar systems
        // Returns: comprehensive conversion data with caching
        // Includes: gregorian, jalali, unix, iso8601, all supported calendars
    }
}
```

**Features**:
- Universal conversion between 15+ calendar systems
- Input validation and error handling
- Multi-format date conversion with caching
- Calendar system categorization (modern, historical, ancient, technical)
- Lazy loading support for performance optimization
- Comprehensive output with Julian Day Number backbone

#### Test Runner Support Services
- **ProgressTrackingService** – Maintains real-time execution statistics, renders ANSI-friendly progress bars, and supplies the interactive console with per-suite metrics.
- **ErrorRecoveryService** – Diagnoses test failures, coordinates exponential backoff retries, and logs contextual diagnostics through `TestExecutionException` metadata for resilient parallel execution.

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

### Laravel Fortify Integration
**Configuration**: `config/fortify.php`

Fortify provides:
- User registration
- Login/logout
- Password reset
- Email verification
- Two-factor authentication
- Password confirmation

### Authentication Flow
```
1. User Registration
   ├── Email verification required
   ├── Password validation
   └── Automatic login after verification

2. Two-Factor Authentication
   ├── TOTP (Time-based One-Time Password)
   ├── Recovery codes generation
   └── QR code setup

3. Session Management
   ├── Web guard authentication
   ├── Session-based storage
   └── Remember me functionality
```

### Authentication Components
```
livewire/auth/
├── login.blade.php              # Login form
├── register.blade.php           # Registration form
├── forgot-password.blade.php    # Password reset request
├── reset-password.blade.php     # Password reset form
├── verify-email.blade.php       # Email verification
├── confirm-password.blade.php   # Password confirmation
└── two-factor-challenge.blade.php # 2FA challenge
```

### Authorization Structure
- **Middleware**: `auth`, `verified`, `password.confirm`
- **Guards**: `web` (default)
- **Route Protection**: Applied at route level

```php
Route::middleware(['auth', 'verified'])->group(function () {
    // Protected routes
});
```

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

#### 15+ Calendar Service Architecture
```
app/Services/
├── DateTimeConverterService.php    # Main orchestrator
├── CalendarConversionService.php   # Base conversion utilities
├── JulianDayService.php           # Universal Julian Day conversions
├── HebrewCalendarService.php      # Jewish calendar (lunar-solar)
├── IslamicCalendarService.php     # Hijri lunar calendar
├── ChineseCalendarService.php     # Chinese lunisolar with zodiac
├── JapaneseCalendarService.php    # Imperial era-based calendar
├── FrenchRepublicanService.php    # Revolutionary calendar (1793-1805)
├── CopticCalendarService.php      # Egyptian Christian calendar
├── EthiopianCalendarService.php   # Traditional Ethiopian calendar
├── EgyptianCalendarService.php    # Ancient Egyptian civil calendar
├── BabylonianCalendarService.php  # Ancient Mesopotamian lunar
├── AssyrianCalendarService.php    # Ancient Assyrian calendar
├── AncientMacedonianService.php   # Ancient Macedonian calendar
├── MayanCalendarService.php       # Complex Mesoamerican system
└── AncientAstronomyService.php    # Astronomical calculations
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
Parallel conversion to all 15+ calendar systems:
├── Modern Calendars (Gregorian, Persian, Hebrew, Islamic)
├── Historical Calendars (Julian, French Republican, Coptic, Ethiopian, Egyptian)
├── Ancient Calendars (Babylonian, Assyrian, Ancient Macedonian, Mayan)
└── East Asian Calendars (Chinese Lunisolar, Japanese Era-based)
    ↓
Comprehensive output with:
├── Native date components (year, month, day)
├── Cultural names in original scripts
├── English transliterations
├── Historical context and era information
├── Julian Day Numbers
├── Unix timestamps
└── ISO 8601 formatting
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
```

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
└── 2025_11_10_000000_create_mayan_calendar_data_table.php
```

### Schema Design

#### Users Table
```sql
users
├── id (bigint, primary key)
├── name (varchar)
├── email (varchar, unique)
├── email_verified_at (timestamp, nullable)
├── password (varchar)
├── remember_token (varchar, nullable)
├── two_factor_secret (text, nullable)        # 2FA secret
├── two_factor_recovery_codes (text, nullable) # Recovery codes
├── two_factor_confirmed_at (timestamp, nullable) # 2FA confirmation
├── created_at (timestamp)
└── updated_at (timestamp)
```

#### Saved Conversions Table
```sql
saved_conversions
├── id (bigint, primary key)
├── user_id (bigint, foreign key to users.id, nullable) # Null for guest conversions
├── conversion_data (json)                              # Full conversion results
├── input_date (varchar)                               # Original input date
├── created_at (timestamp)
└── updated_at (timestamp)
```

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
**Client-side**: Alpine.js reactive data
**Server-side**: Livewire component state
**Synchronization**: Livewire entanglement

```javascript
// Alpine.js state management
x-data="{
    showResults: @entangle('conversionResults').live,
    inputValue: @entangle('dateInput').live
}"
```

## Testing Architecture

### Testing Framework Stack
- **Primary**: PestPHP 4.1+ (PHP testing framework)
- **Laravel Integration**: PestPHP Laravel plugin
- **Browser Testing**: Laravel Dusk 8.2+
- **Test Database**: Separate `testing` database

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
│   ├── Settings/               # Settings functionality tests
│   │   ├── PasswordUpdateTest.php           # Password change
│   │   ├── ProfileUpdateTest.php            # Profile modification
│   │   └── TwoFactorAuthenticationTest.php  # 2FA setup/management
│   ├── Livewire/               # Livewire component tests
│   │   ├── Components/         # Modular component tests
│   │   │   ├── CalendarQuickActionsTest.php     # Quick action buttons
│   │   │   ├── CalendarResultsGridTest.php      # Conversion display grid
│   │   │   ├── CalendarSearchFilterTest.php     # Calendar filtering
│   │   │   └── SavedConversionsSectionTest.php  # Saved conversions
│   │   └── LandingPageTest.php # Main landing page component testing
│   ├── DashboardTest.php       # Dashboard tests
│   └── ExampleTest.php         # Example feature test
├── Unit/                       # Unit tests
│   ├── Services/               # Service layer tests
│   │   ├── ChineseCalendarServiceTest.php   # Chinese calendar service
│   │   ├── CopticCalendarServiceTest.php    # Coptic calendar service
│   │   ├── DateTimeConverterServiceTest.php # Main converter service
│   │   ├── EgyptianCalendarServiceTest.php  # Egyptian calendar service
│   │   ├── EthiopianCalendarServiceTest.php # Ethiopian calendar service
│   │   └── JapaneseCalendarServiceTest.php  # Japanese calendar service
│   └── ExampleTest.php         # Example unit test
└── Browser/                    # Dusk browser tests
    ├── Components/             # Component-specific browser tests (empty)
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
            - '8080:80'      # Laravel application
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

### ADR-004: Docker Development Environment
**Decision**: Use Laravel Sail for development
**Rationale**:
- Consistent development environment
- Easy onboarding for new developers
- Isolated service dependencies
- Production-like environment

---

This architecture overview provides a comprehensive understanding of EpochBridge's technical implementation, design decisions, and operational considerations. The architecture supports scalability, maintainability, and developer productivity while delivering a robust date conversion platform.
