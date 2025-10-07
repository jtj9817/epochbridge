# EpochBridge

A modern Laravel 12 application built with the TALL Stack (Tailwind CSS, Alpine.js, Livewire, Laravel) featuring complete authentication and calendar functionality.

## Features

- **Date & Time Conversion**: Real-time conversion between Gregorian and Persian/Jalali calendars
- **Multi-Format Output**: Unix timestamps, ISO 8601, and detailed calendar components
- **Interactive Landing Page**: Modern interface with quick-action buttons (Today, Yesterday, Tomorrow)
- **TALL Stack Architecture**: Tailwind CSS 4, Alpine.js, Livewire 3, Laravel 12
- **Authentication System**: Complete auth flow with Laravel Fortify
- **Two-Factor Authentication**: Enhanced security with recovery codes
- **Email Verification**: Required for new user accounts
- **Calendar Integration**: Extended calendar functionality with Persian/Jalali support
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
├── Http/Controllers/        # HTTP controllers
├── Livewire/               # Livewire components
├── Models/                 # Eloquent models
├── Providers/              # Service providers
└── Services/               # Business logic services

resources/
├── css/                    # Tailwind CSS files
├── js/                     # JavaScript files
├── views/
│   ├── components/         # Blade components
│   ├── livewire/          # Livewire Volt components
│   └── pages/             # Page-level Volt components

tests/
├── Feature/               # Feature tests
├── Unit/                  # Unit tests
└── Browser/               # Dusk browser tests
```

## Key Features

### Date Conversion System
The core functionality of EpochBridge is powered by the `DateTimeConverterService` which provides:

- **Multi-Calendar Support**: Gregorian ↔ Persian/Jalali conversion
- **Comprehensive Output**: Returns detailed date components including:
  - Year, month name, day, day of week
  - English transliterations for Persian calendar
  - Unix timestamps and ISO 8601 formatting
- **Input Validation**: Robust error handling for invalid date formats
- **Timezone Aware**: Consistent date processing with timezone support

### Calendar Integration
- **fisharebest/ext-calendar**: Extended calendar functionality
- **morilog/jalali**: Persian/Jalali calendar support
- Date conversion utilities for both Gregorian and Jalali systems

### Interactive Landing Page
Located at `resources/views/livewire/landing.blade.php`:
- Real-time date conversion interface
- Quick-action buttons (Today, Yesterday, Tomorrow)
- Input validation with user-friendly error messages
- Loading states and responsive design
- Livewire Volt single-file component architecture

### Livewire Volt
Single-file components combining PHP logic and Blade templates:
- **Landing page**: `resources/views/livewire/landing.blade.php` - Main date conversion interface
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