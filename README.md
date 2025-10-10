# EpochBridge

A comprehensive calendar conversion platform built with Laravel 12 and the TALL Stack, supporting 15+ historical and modern calendar systems with complete authentication and user management features.

## Features

### Calendar Conversion System
- **15+ Calendar Systems**: Comprehensive support including:
  - **Modern**: Gregorian, Persian/Jalali, Islamic (Hijri), Hebrew
  - **Historical**: Julian, French Republican, Coptic, Ethiopian, Egyptian
  - **Ancient**: Babylonian, Assyrian, Ancient Macedonian, Mayan
  - **East Asian**: Chinese Lunisolar, Japanese Era-based
- **Multi-Format Output**: Unix timestamps, ISO 8601, Julian Day Numbers, and detailed calendar components
- **Real-time Conversion**: Instant conversion between any supported calendar systems
- **Historical Accuracy**: Authentic calculations with astronomical data integration

### User Experience
- **Interactive Landing Page**: Modern interface with comprehensive calendar displays
- **Conversion Management**: Save and manage conversions (authenticated users) or use local storage (guests)
- **Clipboard Integration**: Copy conversion results with one click
- **Quick Actions**: Today, Yesterday, Tomorrow buttons for rapid navigation
- **Responsive Design**: Optimized for desktop and mobile devices

### Technical Stack
- **TALL Stack Architecture**: Tailwind CSS 4, Alpine.js, Livewire 3, Laravel 12
- **Authentication System**: Complete auth flow with Laravel Fortify
- **Two-Factor Authentication**: Enhanced security with recovery codes
- **Email Verification**: Required for new user accounts
- **Modern UI**: Built with Flux UI component library
- **Livewire Volt**: Single-file components for rapid development

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS 4
- **Database**: MySQL 8.0
- **Build Tool**: Vite with HMR
- **Testing**: PestPHP, Laravel Dusk
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

4. Start the development environment
```bash
# Using Laravel Sail (recommended)
./vendor/bin/sail up

# Or using Composer script
composer run dev
```

5. Run migrations
```bash
./vendor/bin/sail artisan migrate
```

6. Start the frontend development server
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

## Architecture

### Authentication
- **Laravel Fortify**: Handles all authentication logic
- **Livewire Volt Components**: Modern single-file components for auth forms
- **Two-Factor Authentication**: TOTP with recovery codes
- **Email Verification**: Required for account activation

### TALL Stack Components
- **Tailwind CSS 4**: Utility-first CSS with `@source` directives
- **Alpine.js**: Client-side interactivity without server roundtrips
- **Livewire 3**: Full-stack framework for dynamic interfaces
- **Laravel 12**: Modern backend with PHP 8.2+ features

### Project Structure
```
app/
├── Exceptions/             # Custom exception handling
│   ├── CalendarConversionException.php   # Base calendar exception (abstract)
│   ├── CalendarMalformedDateException.php # Date format parsing errors
│   ├── CalendarRangeException.php        # Date range validation errors
│   ├── CalendarCalculationException.php  # Astronomical calculation failures
│   └── CalendarValidationException.php   # Date component validation errors
├── Http/Controllers/        # HTTP controllers
├── Livewire/               # Livewire components
├── Models/                 # Eloquent models
│   ├── User.php           # User model with saved conversions
│   ├── SavedConversion.php # User conversion history with date casting
│   ├── JapaneseEra.php    # Japanese era data
│   ├── HistoricalCalendarData.php # Ancient calendar reference data
│   └── MayanCalendarData.php # Mayan calendar calculations
├── Providers/              # Service providers
└── Services/               # Calendar conversion services
    ├── DateTimeConverterService.php    # Main conversion orchestrator with caching
    ├── CalendarConversionService.php   # Base conversion utilities
    ├── JulianDayService.php           # Julian Day calculations
    ├── HebrewCalendarService.php      # Hebrew calendar
    ├── IslamicCalendarService.php     # Islamic/Hijri calendar
    ├── ChineseCalendarService.php     # Chinese lunisolar calendar
    ├── JapaneseCalendarService.php    # Japanese era-based calendar
    ├── FrenchRepublicanService.php    # French Revolutionary calendar
    ├── CopticCalendarService.php      # Coptic calendar
    ├── EthiopianCalendarService.php   # Ethiopian calendar
    ├── EgyptianCalendarService.php    # Ancient Egyptian calendar
    ├── BabylonianCalendarService.php  # Babylonian calendar
    ├── AssyrianCalendarService.php    # Assyrian calendar
    ├── AncientMacedonianService.php   # Ancient Macedonian calendar
    ├── MayanCalendarService.php       # Mayan calendar system
    └── AncientAstronomyService.php    # Astronomical calculations

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
    │   │   └── saved-conversion-item.blade.php     # Individual saved item
    │   ├── auth/                          # Authentication forms
    │   └── settings/                      # User settings pages
    └── pages/             # Page-level Volt components

database/
├── migrations/            # Database schema
│   ├── *_create_saved_conversions_table.php
│   ├── *_create_japanese_eras_table.php
│   ├── *_create_historical_calendar_data_table.php
│   └── *_create_mayan_calendar_data_table.php
└── seeders/              # Database seeders
    ├── JapaneseEraSeeder.php
    └── MayanCalendarDataSeeder.php

tests/
├── Feature/               # Feature tests
│   └── Livewire/         # Livewire component tests
├── Unit/                  # Unit tests
│   └── Services/         # Calendar service tests
└── Browser/               # Dusk browser tests
    ├── LandingPageTest.php      # Full page interaction tests
    └── Components/              # Component-specific browser tests
```

## Key Features

### Multi-Calendar Conversion Engine
The core functionality is powered by `DateTimeConverterService` and 15+ specialized calendar services:

- **Universal Conversion**: Convert between any of the 15+ supported calendar systems
- **Comprehensive Output**: Detailed components for each calendar including:
  - Year, month name, day, day of week in native scripts
  - English transliterations and romanizations
  - Cultural and historical context
  - Unix timestamps, ISO 8601, and Julian Day Numbers
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

#### Modern Calendars
- **Gregorian**: International standard civil calendar
- **Persian/Jalali**: Official calendar of Iran and Afghanistan  
- **Islamic (Hijri)**: Lunar calendar used in Islamic countries
- **Hebrew**: Jewish calendar with lunar-solar calculations

#### Historical Calendars
- **Julian**: Roman calendar, predecessor to Gregorian
- **French Republican**: Revolutionary calendar (1793-1805)
- **Coptic**: Egyptian Christian calendar
- **Ethiopian**: Traditional calendar of Ethiopia
- **Egyptian**: Ancient Egyptian civil calendar

#### Ancient Calendars
- **Babylonian**: Ancient Mesopotamian lunar calendar
- **Assyrian**: Ancient Assyrian calendar system
- **Ancient Macedonian**: Calendar of ancient Macedonia
- **Mayan**: Complex Mesoamerican calendar system

#### East Asian Calendars
- **Chinese Lunisolar**: Traditional Chinese calendar with zodiac
- **Japanese Era-based**: Calendar with imperial era names

### User Features
- **Conversion Management**: Authenticated users can save and organize conversion history
- **Guest Storage**: Non-authenticated users utilize local browser storage
- **Clipboard Integration**: One-click copying of conversion results
- **Bulk Operations**: Save multiple conversions simultaneously
- **Search & Filter**: Find saved conversions by date or calendar type

### Interactive Landing Page
Located at `resources/views/livewire/landing.blade.php` with modular component architecture:
- **Comprehensive Display**: All 15+ calendars shown simultaneously using individual calendar cards
- **Modular Design**: Separated into focused components for maintainability:
  - Calendar input section with validation
  - Quick action buttons (Today, Yesterday, Tomorrow)  
  - Results grid with lazy loading and filtering
  - Saved conversions management
  - Search and filter functionality
- **Real-time Updates**: Instant conversion with Livewire Volt `on()` event listeners
- **Visual Design**: Unique styling for each calendar system with cultural icons
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
- **Feature Tests**: Authentication flows, settings, dashboard
- **Browser Tests**: Full user interactions with Dusk
- **Unit Tests**: Individual component testing

Run the full test suite:
```bash
php artisan test
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