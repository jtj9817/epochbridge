# EpochBridge Project Context

EpochBridge is a comprehensive calendar conversion platform built with Laravel 12 and the TALL Stack (Tailwind CSS 4, Alpine.js, Livewire 3, Laravel 12). It supports 19 calendar systems across modern, historical, ancient, and technical formats.

## Project Overview

- **Core Purpose**: Provide high-accuracy conversions between 19 different calendar systems, including astronomical calculations for lunar and lunisolar calendars.
- **Key Features**:
  - **Universal Conversion**: Supports Gregorian, Jalali, Islamic, Hebrew, Julian, French Republican, Coptic, Ethiopian, Egyptian, Babylonian, Assyrian, Ancient Macedonian, Mayan, Chinese Lunisolar, Japanese Era, ISO 8601, Unix, and Julian Day Numbers.
  - **User Management**: Built with Laravel Fortify and Socialite (Google OAuth), supporting 2FA and account linking.
  - **Saved Conversions**: Allows users (and guests via localStorage) to save conversions with notes.
  - **Interactive Test Runner**: A custom Artisan command (`test:runner`) with parallel execution, real-time progress, and Markdown reporting.
- **Architecture**: Layered Monolithic (Presentation -> Service -> Repository -> Data). Uses the Adapter Pattern for complex calendar systems.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+), Fortify, Socialite.
- **Frontend**: Livewire 3 (Volt), Alpine.js, Tailwind CSS 4, Flux UI.
- **Database**: MySQL 8.0.
- **Testing**: PestPHP, Laravel Dusk, Pest Browser Plugin.
- **Containerization**: Laravel Sail (Dev), Docker (Prod).

## Building and Running

### Development
```bash
# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Start full development environment (server, queue, logs, vite)
composer run dev

# Or use Laravel Sail
./vendor/bin/sail up
```

### Production
```bash
# Build assets
npm run build

# Optimize Laravel
php artisan optimize
```

## Testing

The project uses Pest for testing. Always run tests before committing.

- **Run all tests**: `php artisan test`
- **Interactive Test Runner**: `php artisan test:runner`
- **Browser Tests**: `php artisan dusk`
- **Coverage**: `php artisan test --coverage` (or via sail as per project memories)

## Development Conventions

### Coding Style
- **Formatting**: Adhere to Laravel conventions. Use `./vendor/bin/pint` to format code.
- **Type Hinting**: Use strict typing for PHP classes and methods.
- **Exceptions**: Use custom exceptions from `app/Exceptions/` (e.g., `CalendarConversionException`, `CalendarValidationException`).

### Architecture & Design
- **Services**: Business logic belongs in `app/Services/`. `DateTimeConverterService` is the primary orchestrator.
- **Calendar Adapters**: New complex calendar systems should implement `CalendarConversionService` and use the Adapter Pattern if they wrap external libraries.
- **Livewire Volt**: Use Volt for frontend components. Components are located in `resources/views/livewire/`.
- **Cultural Accuracy**:
  - Use native/authentic names for months/days.
  - Provide English transliterations.
  - Use `ensureTimezone()` in calendar services for geographical accuracy.
  - Add disclaimers for scholarly uncertainty in `disclaimer` slots of calendar cards.

### Security
- **ABSOLUTE RULE**: DO NOT READ the user's `.env` file. If you need to verify a configuration, ask for permission or look at `.env.example`.
- **Secrets**: Never commit secrets or API keys. Use environment variables.
- **Data Protection**: Sensitive data (like OAuth tokens) should be encrypted at rest using Laravel's encrypted casting.

## Key Files

- `app/Services/DateTimeConverterService.php`: Main conversion logic.
- `app/Services/CalendarConversionService.php`: Base class for calendar implementations.
- `config/calendars.php`: Configuration for specific calendar systems.
- `app/Console/Commands/InteractiveTestRunnerCommand.php`: The custom test runner.
- `resources/views/livewire/landing.blade.php`: Main application entry point.
- `docs/`: Extensive documentation on architecture, cultural accuracy, and more.
