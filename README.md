# EpochBridge

A calendar conversion platform built with Laravel 12 and a React 19 / Inertia frontend, supporting 19 calendar systems (15 historical and modern calendars plus technical formats) with complete authentication, guest-friendly saved conversions, and a Wikimedia "On This Day" integration.

The frontend was migrated from Livewire/Volt to React/Inertia in April 2026, and the Livewire runtime was removed entirely in September 2026. Blade now exists only as the Inertia shell.

## Features

### Calendar Conversion System
- **19 Calendar Systems**: Comprehensive support including:
  - **Modern**: Gregorian, Persian/Jalali, Islamic (Hijri), Hebrew
  - **Historical**: Julian, French Republican (with proleptic support), Coptic, Ethiopian, Egyptian
  - **Ancient**: Babylonian, Assyrian, Ancient Macedonian, Mayan
  - **East Asian**: Chinese Lunisolar, Japanese Era-based
  - **Technical formats**: Unix timestamp, ISO 8601, Julian Day Number, Modified Julian Day
- **Extensible Architecture**: Adapter pattern with dependency injection for calendar service customization (see `docs/calendar-integration-guide.md`)
- **Multi-Format Output**: Unix timestamps, ISO 8601, Julian Day Numbers, and detailed calendar components
- **Historical Accuracy**: Astronomical calculations (Meeus algorithms with ΔT corrections for the Chinese lunisolar calendar)

### User Experience
- **Interactive Landing Page**: Stardew-inspired pixel UI (`.sd-*` design system in `resources/css/stardew*.css`) with rotating seasonal city backdrops
- **Conversion Management**: Save and manage conversions
  - **Authenticated Users**: Database-backed history with search, notes (up to 2048 characters), and pagination
  - **Guest Users**: Up to 3 conversions persisted in browser `localStorage`, automatically migrated on login
- **Toast Notifications**: Feedback for save/delete/error operations (4-second auto-dismiss, `ToastProvider`)
- **Clipboard Integration**: Copy conversion results with one click
- **Quick Actions**: Today, Yesterday, Tomorrow buttons for rapid navigation
- **On This Day**: Historical events for the converted date via the Wikimedia API (cached locally; see `docs/wikimedia-scraper-architecture.md`)

### Technical Stack
- **Backend**: Laravel 12 (PHP 8.2+), Laravel Fortify (authentication), Laravel Socialite (Google OAuth)
- **Frontend**: React 19 + TypeScript, Inertia 2, Tailwind CSS 4, Vite
- **Database**: MySQL 8.0
- **Testing**: Pest 4 (Feature/Unit), Laravel Dusk (browser), Vitest + Testing Library (React components)
- **Containerization**: Laravel Sail (development), multi-stage Docker with Nginx + PHP-FPM + Supervisord (production)

## Quick Start

### Prerequisites

- PHP 8.2+, Composer
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
# Add to .env file (defaults match .env.example port 8081):
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8081/auth/google/callback
```

To obtain Google OAuth credentials, see `docs/google-oauth-integration.md`.

5. Configure Wikimedia On This Day (optional)
```bash
# Add to .env file:
WIKIMEDIA_API_TOKEN=your-wikimedia-token
WIKIMEDIA_USER_AGENT="EpochBridge/1.0 (https://example.com; mailto:hello@example.com)"
```

6. Start the development environment and run migrations
```bash
# Using Laravel Sail (recommended)
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate

# Or without Docker
composer run dev        # app server, queue listener, Pail logs, Vite watcher
php artisan migrate
```

The application will be available at the port configured by `APP_PORT` (default `8081`); the Vite dev server uses `VITE_PORT` (default `5173`). Both are set in `.env`.

## Development Commands

### Laravel Sail (Docker)
```bash
./vendor/bin/sail up -d     # start MySQL, queue, Dusk containers
./vendor/bin/sail stop      # stop the stack
./vendor/bin/sail artisan migrate
```

### Composer Scripts
```bash
composer run dev    # server + queue + logs + Vite watcher together
composer run test   # config:clear + php artisan test
```

### Testing
```bash
php artisan test                          # full Pest suite
php artisan test --testsuite=Feature      # feature tests only
php artisan test --testsuite=Unit         # unit tests only
npm run test:run                          # Vitest React component tests
php artisan dusk                          # browser tests (Sail must be running)
```

### Code Quality
```bash
./vendor/bin/pint    # format PHP (PSR-12)
```

## Architecture

### Authentication
- **Laravel Fortify**: Registration, login, password reset, email verification (enabled September 2026; existing users were grandfathered as verified), two-factor authentication
- **Laravel Socialite**: Google Sign-In with PKCE; OAuth users are auto-verified, and linking a Google account into an existing *unverified* email account is refused (account pre-hijacking guard in `app/Http/Controllers/Auth/SocialAuthController.php`)
- Rate limiting: login 5/day per email+IP, registration 10/hour per IP, password reset link 5/min per IP

### HTTP Surface
- `routes/web.php` — Inertia pages (landing, dashboard, settings)
- `routes/auth.php` — auth pages, Fortify POST endpoints (throttled), Google OAuth redirect/callback, logout, account deletion
- `routes/frontend.php` — session-backed JSON API under `/frontend/*` for the React app (conversions, saved conversions, quick actions, on-this-day). Public endpoints are rate-limited to 60 requests/minute/IP.

### Project Structure
```
app/
├── Actions/Fortify/          # Fortify user actions (create, update, reset)
├── Console/Commands/         # Interactive test runner, Wikimedia scrapers
├── Contracts/Calendar/       # French Republican adapter interface
├── Exceptions/               # Calendar exception hierarchy
├── Http/
│   ├── Controllers/
│   │   ├── Auth/             # Social auth, logout, account deletion
│   │   ├── FrontendApi/      # JSON endpoints for the React app
│   │   └── React/            # Inertia page controllers (landing, auth, settings)
│   ├── Middleware/           # ForceHttps, SetSecurityHeaders, HandleInertiaRequests
│   ├── Requests/Frontend/    # Form requests for the frontend API
│   └── Resources/Frontend/   # API resources
├── Jobs/                     # ExecuteTestSuiteJob, ScrapeWikimediaBatch
├── Models/                   # User, SocialAccount, SavedConversion, OnThisDayEntry, ...
├── Services/                 # Calendar services + Wikimedia + test-runner services
└── Support/Calendars/        # French Republican ext-calendar adapter

resources/js/                 # React frontend (Inertia)
├── pages/                    # LandingPage, DashboardPage, Auth/*, Settings/*
├── components/               # calendars/, forms/, modals/, saved-conversions/
├── hooks/                    # useSavedConversions, useGuestSavedConversions, useOnThisDay
├── lib/                      # api.ts (CSRF-aware fetch client), guest-storage.ts, theme.ts
├── layouts/                  # AppLayout, AuthLayout, SettingsLayout
└── providers/                # ToastProvider

database/migrations/          # Schema (see docs/architecture-overview.md)
tests/                        # Pest Feature/Unit, Dusk Browser, Vitest *.test.tsx colocated in resources/js
docs/                         # Deep-dive documentation
```

## Interactive Test Runner

EpochBridge includes an interactive test runner with parallel execution, progress tracking, error recovery, and Markdown reporting.

```bash
# Interactive mode with menu selection
php artisan test:runner

# Direct execution with custom report file
php artisan test:runner --output-file=storage/reports/test-report.md

# Verbose mode
php artisan test:runner --verbose
```

Key settings live in `config/test-runner.php` and can be overridden with environment variables:

| Setting | Env Variable | Default | Description |
| --- | --- | --- | --- |
| Parallel mode | `TEST_RUNNER_PARALLEL` | `true` | Enable parallel test execution |
| Max processes | `TEST_RUNNER_PROCESSES` | `4` | Number of parallel test processes |
| Timeout (seconds) | `TEST_RUNNER_TIMEOUT` | `300` | Maximum execution time per test suite |
| Retry attempts | `TEST_RUNNER_RETRY_ATTEMPTS` | `1` | Number of retry attempts for failed tests |
| Report file | `TEST_RUNNER_REPORT_FILE` | `test-report.md` | Output file for Markdown reports |
| Report path | `TEST_RUNNER_REPORT_PATH` | project root | Directory for reports |

The runner also applies per-environment overrides from `config/test-runner.php` (`local`, `ci`).

## Environment Configuration

| Variable | Required | Purpose |
| --- | --- | --- |
| `APP_PORT` | no (default `8081`) | Dev server port |
| `VITE_PORT` | no (default `5173`) | Vite dev-server port |
| `FORWARD_DB_PORT` | no (default `3307`) | Host port for MySQL in Sail |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / `GOOGLE_REDIRECT_URI` | for Google Sign-In | OAuth credentials |
| `WIKIMEDIA_API_TOKEN` / `WIKIMEDIA_USER_AGENT` | for On This Day | Wikimedia API access |

## Documentation

- [Architecture overview](docs/architecture-overview.md) — system design, data flow, security
- [Calendar integration guide](docs/calendar-integration-guide.md) — adding a new calendar system
- [Calendar systems reference](docs/calendar-systems-reference.md) — supported systems and their data
- [Google OAuth integration](docs/google-oauth-integration.md) — setup and flows
- [Guest conversions guide](docs/guest-conversions-guide.md) — localStorage saves and migration
- [Wikimedia scraper architecture](docs/wikimedia-scraper-architecture.md) — On This Day pipeline
- [CHANGELOG](docs/CHANGELOG.md) — notable changes by date

Older plan documents in `docs/` are historical records of completed work and are marked as such.

## Production Deployment

### Option 1: Docker (Recommended)

1. Build and run the image
```bash
docker build -t epochbridge:latest .
docker run -d \
  --name epochbridge \
  -p 80:80 \
  -e APP_KEY=your-app-key \
  -e DB_HOST=your-db-host \
  -e DB_DATABASE=epochbridge \
  -e DB_USERNAME=your-username \
  -e DB_PASSWORD=your-password \
  epochbridge:latest
```

**Docker architecture**: multi-stage build (Composer deps → Node/Vite assets → runtime), PHP 8.2-FPM + Nginx + Supervisord. Files: `Dockerfile`, `docker/nginx.conf`, `docker/supervisord.conf`, `docker/entrypoint.sh`, `.dockerignore`.

### Option 2: Traditional Deployment

```bash
npm run build
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
```

Set `APP_ENV=production` and `APP_DEBUG=false`. HTTPS is enforced in production (`URL::forceScheme('https')` in `AppServiceProvider`), and security headers (CSP, HSTS on TLS, frame-deny) are applied by `SetSecurityHeaders` middleware.

## License

[Add your license information here]
