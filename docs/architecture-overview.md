# EpochBridge Architecture Overview

## Table of Contents
1. [Application Overview](#application-overview)
2. [Technology Stack](#technology-stack)
3. [System Architecture](#system-architecture)
4. [Application Layers](#application-layers)
5. [Frontend Architecture](#frontend-architecture)
6. [Frontend JSON API](#frontend-json-api)
7. [Authentication Architecture](#authentication-architecture)
8. [Calendar System Integration](#calendar-system-integration)
9. [Database Architecture](#database-architecture)
10. [Wikimedia On This Day Pipeline](#wikimedia-on-this-day-pipeline)
11. [Testing Architecture](#testing-architecture)
12. [Build & Deployment Architecture](#build--deployment-architecture)
13. [Security Architecture](#security-architecture)
14. [Performance Considerations](#performance-considerations)
15. [Architecture Decision Records](#architecture-decision-records)

> Updated September 2026 after the React/Inertia migration (April 2026) and the removal of the Livewire/Volt runtime (September 2026).

## Application Overview

EpochBridge is a calendar conversion platform built on Laravel 12 with a React 19 + Inertia frontend. The application supports 19 calendar systems (historical, modern, and ancient calendars plus technical formats) with complete user management, persistent conversion history for both authenticated and guest users, and a Wikimedia "On This Day" integration.

### Core Purpose
- **Primary Function**: Universal calendar conversion between 19 calendar systems including modern, historical, ancient calendars, and technical formats
- **Target Users**: Historians, developers, researchers, genealogists, and anyone working with diverse calendar systems
- **Key Features**:
  - Real-time conversion between 19 calendar systems
  - Historical accuracy with astronomical calculations
  - Persistent conversion storage for authenticated and guest users
  - Guest data migration to the database on authentication
  - Notes support for annotating saved conversions (up to 2048 characters)
  - Clipboard integration and quick actions (Today/Yesterday/Tomorrow)
  - "On This Day" historical events from the Wikimedia API
  - Comprehensive authentication (email verification, 2FA, Google OAuth)

## Technology Stack

### Backend Technologies
- **Framework**: Laravel 12 (PHP 8.2+)
- **Authentication**: Laravel Fortify 1.30+, Laravel Socialite 5 (Google OAuth)
- **Frontend glue**: Inertia 2 (`inertiajs/inertia-laravel`), Tighten Ziggy 2 (named routes in JS)
- **Database**: MySQL 8.0 (via Docker)
- **Calendar Libraries**:
  - `fisharebest/ext-calendar` 2.6+ (Hebrew, Islamic, French Republican calendars)
  - `morilog/jalali` 3.* (Persian/Jalali calendar support)
- **Custom Calendar Services**: 14 specialized services for historical and ancient calendars

### Frontend Technologies
- **UI Framework**: React 19 with TypeScript
- **Routing/Page bridge**: Inertia 2 (`@inertiajs/react`)
- **CSS Framework**: Tailwind CSS 4 with first-party Vite integration
- **Design System**: Custom Stardew-inspired pixel UI (`.sd-*` classes in `resources/css/stardew.css` and `resources/css/stardew-frames.css`)
- **Build Tool**: Vite 7 with Hot Module Replacement (HMR)
- **Component Tests**: Vitest 4 + Testing Library (React Testing Library, jest-dom, user-event)

### Development & Testing
- **Testing Framework**: PestPHP 4.1+ with Laravel plugin
- **Browser Testing**: Laravel Dusk 8.2+ (Pest 4 browser plugin also installed)
- **Code Quality**: Laravel Pint 1.18+
- **Development Containerization**: Laravel Sail 1.41+ (Docker)
- **Production Containerization**: Multi-stage Dockerfile with PHP 8.2-FPM, Nginx, Supervisord
- **Logging**: Laravel Pail 1.2+

> Livewire, Volt, Alpine.js, and Flux UI were fully removed in September 2026 (`a144ac3`, `16c4d69`). The `livewire/flux` entry in `composer.json` is an unused leftover.

## System Architecture

### High-Level Architecture Pattern
EpochBridge follows a **layered monolithic architecture** with an Inertia SPA-style frontend:

```
┌─────────────────────────────────────┐
│           Presentation Layer        │
│    React 19 components (Inertia)    │
├─────────────────────────────────────┤
│        HTTP / JSON API Layer        │
│  React page controllers + FrontendApi│
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
User Interface (Browser, React)
    ↕ (Inertia page visits + JSON fetch with CSRF token)
Inertia Page Controllers (app/Http/Controllers/React)
FrontendApi Controllers (app/Http/Controllers/FrontendApi)
    ↕ (service injection)
Business Logic Services (app/Services)
    ↕ (Eloquent ORM)
Database Models
    ↕ (MySQL driver)
MySQL Database
```

Inertia page controllers render React pages server-side via `Inertia::render()` and carry shared props through `HandleInertiaRequests` middleware. Interactive data (conversions, saved conversions, On This Day) flows through the session-backed JSON API in `routes/frontend.php`, called by the CSRF-aware fetch client in `resources/js/lib/api.ts`.

## Application Layers

### 1. Presentation Layer
**Location**: `resources/js/`

- **Pages** (`resources/js/pages/`): `LandingPage`, `DashboardPage`, `Auth/*` (login, register, forgot/reset password, verify email, 2FA challenge, confirm password), `Settings/*` (profile, password, appearance, two-factor)
- **Components** (`resources/js/components/`): `calendars/` (results grid + per-calendar cards), `forms/` (input section, quick actions, search filter), `modals/` (save conversion, edit note, On This Day, calendars info), `saved-conversions/` (section + item)
- **Layouts** (`resources/js/layouts/`): `AppLayout`, `AuthLayout`, `SettingsLayout`
- **Hooks** (`resources/js/hooks/`): `useSavedConversions`, `useGuestSavedConversions`, `useOnThisDay`
- **Providers**: `ToastProvider` (success/error/info toasts, 4-second auto-dismiss)

Blade is reduced to the Inertia shell (`resources/views/app.blade.php`).

### 2. Exception Layer
**Location**: `app/Exceptions/`

Custom exception hierarchy for calendar conversion:

- **`CalendarConversionException`** (abstract base) — calendar system context, `[{calendarSystem}]` message prefix, optional date-component debugging data
- **`CalendarMalformedDateException`** — extends `DateMalformedStringException`; unparsable date strings
- **`CalendarRangeException`** — extends `DateRangeError`; dates outside supported ranges
- **`CalendarCalculationException`** — astronomical calculation failures
- **`CalendarValidationException`** — invalid date components
- **`OAuthAccountLinkException`** — OAuth linking refused (e.g. unverified email)
- **`TestExecutionException`** — test runner failures

### 3. Service Layer
**Location**: `app/Services/`

**DateTimeConverterService** — the conversion orchestrator. Registers 14 calendar services in its constructor, computes the primary ("fast") tier (including Gregorian, Jalali, Unix, ISO 8601) plus an additional tier for the remaining calendars, and exposes:
- `convert()` / `convertAdditional()` — tiered conversion with 1-hour caching
- `getFullConversionResults()` — merged full result used by the JSON API
- `convertBetweenCalendars()`, `parseInputDate()`, `formatCalendarResult()` — conversion utilities

**Test runner services** — `TestDiscoveryService`, `TestExecutionService`, `ProgressTrackingService`, `ErrorRecoveryService`, `MarkdownReportService`, `TestSelectionService` power the `test:runner` Artisan command (config in `config/test-runner.php`).

**Wikimedia services** — `Wikimedia/OnThisDayService` and `Wikimedia/OnThisDayCacheService` (see below).

### 4. Controller Layer
**Location**: `app/Http/Controllers/`

- `React/` — Inertia page controllers: `LandingController`, `Dashboard/DashboardPageController`, `Auth/*PageController` (login, register, forgot/reset password, 2FA challenge, confirm password, verify email), `Settings/*PageController` (profile, password, appearance, two-factor)
- `FrontendApi/` — JSON endpoints: `ConversionController`, `QuickActionController`, `OnThisDayController`, `SavedConversionController`, `SavedConversionNoteController`
- `Auth/` — `SocialAuthController` (Google OAuth), `LogoutController`, `DeleteAccountController`

### 5. Repository Layer
**Location**: `app/Models/`

`User` (Fortify `TwoFactorAuthenticatable`, nullable password for OAuth-only accounts, `socialAccounts()` relation), `SocialAccount` (encrypted token casts), `SavedConversion` (see schema below), `OnThisDayEntry`, `JapaneseEra`, `HistoricalCalendarData`, `MayanCalendarData`.

### 6. Provider Layer
**Location**: `app/Providers/` (registered in `bootstrap/app.php`)

- `AppServiceProvider` — binds `FrenchRepublicanCalendarAdapter` → `FrenchRepublicanExtCalendarAdapter`; forces HTTPS in production; defines `login` (5/day per email+IP) and `frontend-public` (60/min per IP) rate limiters
- `FortifyServiceProvider` — Fortify action classes, Inertia views for 2FA challenge / confirm-password / email-verification prompt; `two-factor` (5/min), `auth-register` (10/hour/IP), `auth-forgot-password` (5/min/IP) rate limiters
- `EventServiceProvider`, `TestRunnerServiceProvider`

## Frontend Architecture

### Asset Pipeline
Vite with the Laravel plugin; entry point `resources/js/app.tsx` (see `vite.config.js`). In `vite serve` mode the plugin serves Laravel's `public/` directory so root-relative CSS `url()` references resolve on the dev-server origin.

```
Development:
npm run dev → Vite dev server (VITE_PORT, default 5173) → HMR updates

Production:
npm run build → optimized bundles → public/build/
```

### State Management
State lives in React: `LandingPage` owns conversion state (`dateInput`, `inputType`, `results`, `error`, `isConverting`, `searchTerm`, `selectedTypes`, modal open/close) and delegates persistence to hooks:

- `useSavedConversions(authenticated)` — loads/saves/deletes/annotates via `lib/api.ts` for authenticated users
- `useGuestSavedConversions` — localStorage-backed guest persistence (see [Guest conversions guide](guest-conversions-guide.md))
- `useOnThisDay` — fetches `/frontend/on-this-day` for the current date

All API calls go through `resources/js/lib/api.ts`, a `fetch` wrapper that sends `X-CSRF-TOKEN` from the page's CSRF meta tag, `Accept: application/json`, and JSON bodies.

### Guest Data Migration
1. Guest saves land in `localStorage` under `savedConversions` (max 3 items, duplicate `original_input` prevented)
2. On login, the frontend posts pending items to `POST /frontend/saved-conversions/migrate`
3. The server validates (≤3 items, per-item payload bounds), deduplicates by `original_input`, inserts in a transaction, and returns `migrated_count`
4. The client clears the localStorage keys after success

### Visual Design
The Stardew pixel design system (`.sd-*` utility classes, pixel fonts, frame styling) lives in `resources/css/stardew.css` and `resources/css/stardew-frames.css`; seasonal city backdrops rotate by season (`resources/js/lib/seasonal-background.ts`, assets under `public/`). Dark mode is handled by `resources/js/lib/theme.ts` with an inline appearance-bootstrap script in the Blade shell.

## Frontend JSON API

Declared in `routes/frontend.php`, mounted with the `web` middleware group (session + CSRF), consumed by the React app:

| Method | Path | Auth | Throttle | Handler |
| --- | --- | --- | --- | --- |
| POST | `/frontend/conversions` | – | `frontend-public` (60/min/IP) | `ConversionController@store` |
| GET | `/frontend/on-this-day` | – | `frontend-public` | `OnThisDayController@show` |
| GET | `/frontend/quick-action` | – | `frontend-public` | `QuickActionController@show` |
| GET | `/frontend/saved-conversions` | auth | – | `SavedConversionController@index` (paginated, 20/page) |
| POST | `/frontend/saved-conversions` | auth | – | `SavedConversionController@store` |
| POST | `/frontend/saved-conversions/migrate` | auth | – | `SavedConversionController@migrate` (≤3 items) |
| PATCH | `/frontend/saved-conversions/{id}/note` | auth | – | `SavedConversionNoteController@update` |
| DELETE | `/frontend/saved-conversions/{id}` | auth (owner) | – | `SavedConversionController@destroy` |

**Validation bounds** (`StoreSavedConversionRequest`, `MigrateGuestConversionsRequest`): `original_input` ≤255 chars, `conversion_data` ≤128 keys and ≤64 KiB (`MaxPayloadBytes` rule), `note` ≤2048 chars. Invalid conversions return 422 with the message; unexpected errors return a generic 500.

There is no `routes/api.php`; the frontend API is the application's JSON surface.

## Authentication Architecture

### Laravel Fortify
**Configuration**: `config/fortify.php`

Enabled features: registration, password reset, **email verification** (enabled September 2026; existing users grandfathered as verified via migration `2026_09_08_052734`), profile information updates, password updates, and two-factor authentication (confirm + password confirmation).

Fortify's Blade views are replaced with Inertia React pages registered in `FortifyServiceProvider` (2FA challenge, confirm-password, email-verification prompt). Registration and password-reset-link POST routes are re-declared in `routes/auth.php` over Fortify's handlers with dedicated throttles.

### Google OAuth (Socialite)
**Controller**: `app/Http/Controllers/Auth/SocialAuthController.php`; routes `auth/{provider}/redirect|callback` restricted to `google`.

- **PKCE** enabled; `access_type=offline` + `prompt=consent` requested
- Provider allowlist (`google` only) — anything else 404s
- **New users** are created with `email_verified_at` set immediately
- **Account linking guard**: linking into an existing account whose email is *unverified* throws `OAuthAccountLinkException` (account pre-hijacking prevention)
- Provider `error_description` is never reflected to the user (attacker-controllable); fixed error messages only
- Access/refresh tokens stored encrypted at rest (`SocialAccount` encrypted casts); hidden from JSON serialization; two-factor attributes hidden from user serialization

### Rate Limiters
| Limiter | Limit | Key |
| --- | --- | --- |
| `login` | 5/day | email + IP |
| `two-factor` | 5/min | login session id |
| `auth-register` | 10/hour | IP |
| `auth-forgot-password` | 5/min | IP |
| `frontend-public` | 60/min | IP |

### Data Models
`users` — nullable `password` (OAuth-only accounts), `avatar` from OAuth, `email_verified_at`, 2FA columns (`two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`).

`social_accounts` — `provider`, `provider_id` (unique together), `access_token`/`refresh_token` (encrypted casts), `expires_in`, `avatar`, `nickname`; cascade delete with the user.

## Calendar System Integration

### Primary Libraries
1. **fisharebest/ext-calendar** — Julian Day Number conversions; Hebrew, Islamic (Hijri), French Republican calendars
2. **morilog/jalali** — Persian/Jalali conversion and month/day names

### Calendar Service Architecture
```
app/Services/
├── DateTimeConverterService.php    # Main orchestrator (tiered + caching)
├── CalendarConversionService.php   # Base conversion utilities
├── JulianDayService.php            # Universal Julian Day Number conversions (astronomical)
├── JulianCalendarService.php       # Julian calendar (historical dates)
├── HebrewCalendarService.php       # Jewish calendar (lunar-solar)
├── IslamicCalendarService.php      # Hijri lunar calendar
├── ChineseCalendarService.php      # Chinese lunisolar calendar (orchestrator)
├── Chinese/                        # Astronomical subsystem
│   ├── ChineseLunisolarCalendar.php
│   ├── AstronomyCalculator.php     # Meeus-based Sun/Moon ephemerides with ΔT
│   └── LunarMonth.php
├── JapaneseCalendarService.php     # Imperial era-based calendar
├── FrenchRepublicanService.php     # Revolutionary calendar with DI & proleptic support
├── CopticCalendarService.php
├── EthiopianCalendarService.php
├── EgyptianCalendarService.php
├── BabylonianCalendarService.php
├── AssyrianCalendarService.php
├── AncientMacedonianService.php
├── MayanCalendarService.php
└── AncientAstronomyService.php     # Astronomical calculations

app/Contracts/Calendar/FrenchRepublicanCalendarAdapter.php   # adapter interface
app/Support/Calendars/FrenchRepublicanExtCalendarAdapter.php # concrete adapter
```

### Conversion Flow
```
User input (date in any supported format)
    ↓
DateTimeConverterService (parseInputDate → Carbon)
    ↓
Julian Day Number backbone
    ↓
Primary tier: fast calendars (hebrew, islamic, french_republican, coptic, ethiopian
               + gregorian/jalali/unix/iso8601 basics)
Additional tier: remaining calendar services
    ↓
Merged output (getFullConversionResults): native date components, cultural names,
transliterations, Julian Day Numbers, Unix timestamps, ISO 8601
```

Note: the Julian *calendar* (historical date system) and the Julian *Day Number* (astronomical day count used as the conversion backbone) are separate concepts.

### Adapter Pattern (French Republican)
The French Republican calendar uses the adapter pattern for testability and strategy swapping:

- **Interface**: `App\Contracts\Calendar\FrenchRepublicanCalendarAdapter` (`jdToYmd`, `ymdToJd`, `jdStart`, `jdEnd`, `isLeapYear`)
- **Implementation**: `App\Support\Calendars\FrenchRepublicanExtCalendarAdapter` (wraps `fisharebest/ext-calendar`, per-instance caching)
- **Binding**: `AppServiceProvider` binds the interface to the implementation
- **Service**: `FrenchRepublicanService` receives the adapter by constructor injection

Configuration in `config/calendars.php`:
```php
'french_republican' => [
    'apply_paris_offset' => true,   // Paris mean solar offset (UT1 + 00:09:21)
    'allow_proleptic' => true,      // extend beyond the historical 1792–1805 range
],
```

### Chinese Calendar Astronomical Architecture
- **ChineseLunisolarCalendar** — suì (year) construction and lunation sequencing; dìngqì solar-term convention for leap-month detection ("first post-solstice lunation lacking a major term"); caches lunation sequences per suì
- **AstronomyCalculator** — Meeus Sun/Moon ephemerides; ΔT corrections (Espenak–Meeus polynomials, 1900–2100); new moons with full periodic corrections; solar terms via Newton iteration on apparent solar longitude; Beijing civil-day boundaries (UTC+8)
- **LunarMonth** — immutable lunation data structure (month number, leap flag, length, start/end)

Handles edge cases such as the 2033 leap month 11.

See [calendar-integration-guide.md](calendar-integration-guide.md) for adding a new calendar system, and [calendar-systems-reference.md](calendar-systems-reference.md) for per-system details.

## Database Architecture

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
├── 2025_10_14_222009_update_saved_conversions_table.php
├── 2025_10_15_040124_add_indexes_to_saved_conversions_table.php
├── 2025_10_20_120000_add_unique_constraint_to_saved_conversions.php
├── 2025_10_24_204329_create_social_accounts_table.php
├── 2025_10_24_204408_modify_users_table_for_social_auth.php
├── 2025_10_29_214730_add_note_column_to_saved_conversions_table.php
├── 2025_11_10_000000_create_mayan_calendar_data_table.php
├── 2026_01_08_000000_create_file_table.php
└── 2026_09_08_052734_grandfather_existing_users_as_email_verified.php
```

### Key Schemas

#### users
Standard Laravel columns plus: nullable `password` (OAuth-only accounts), `avatar`, and Fortify 2FA columns (`two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`).

#### social_accounts
`user_id` (FK, cascade delete), `provider` + `provider_id` (unique together), `access_token` / `refresh_token` (encrypted casts), `expires_in`, `avatar`, `nickname`.

#### saved_conversions
```
├── id
├── user_id (FK → users, cascade delete)     # authenticated saves only — guests use localStorage
├── original_input (string)                  # dedupe key, unique per user
├── conversion_data (JSON, nullable)         # full multi-calendar result
├── gregorian_date / jalali_date / iso8601_string / unix_timestamp   # denormalized for listing
├── note (text, nullable, ≤2048 chars)
├── timestamps
Indexes: created_at; (user_id, created_at); user_id
Unique:  (user_id, original_input)
```

#### on-this-day cache (`file` table)
Language/type/month/day keyed cache of raw Wikimedia responses (unique on `language, type, month, day`), written by `OnThisDayCacheService` and the scraper pipeline.

#### Reference tables
`japanese_eras` (era names, start/end dates), `historical_calendar_data` (calendar_type/data_type/data_content JSON), `mayan_calendar_data` (long count, haab, tzolkin, Gregorian date, Julian Day Number).

## Wikimedia On This Day Pipeline

- **Live path**: `GET /frontend/on-this-day` → `OnThisDayController` → `Wikimedia/OnThisDayService` (api.wikimedia.org, bearer token + required user-agent) → `OnThisDayCacheService` (file table, cache-first)
- **Scraper path**: Artisan commands `scrape:on-this-day` and `scrape:on-this-day:repair` (`app/Console/Commands/`), batched via `ScrapeWikimediaBatch` job; `scrape:on-this-day:repair --all` is scheduled daily at 02:00 in `bootstrap/app.php`
- **Config**: `config/services.php` → `wikimedia` (`base_url`, `token`, `user_agent`, `verbose_logs`)

Details: [wikimedia-scraper-architecture.md](wikimedia-scraper-architecture.md).

## Testing Architecture

### Test Types
- **Pest feature tests** (`tests/Feature/`) — auth flows (`Auth/`: authentication, registration, password reset/confirmation, email verification, 2FA challenge, rate limits, Google OAuth), frontend API controllers (`FrontendApi/`), Inertia React pages (`React/` — landing, dashboard, auth pages, settings pages), settings (`Settings/`), dashboard, data migration, security headers, plus test-runner integration/jobs
- **Pest unit tests** (`tests/Unit/`) — calendar services (Chinese, Coptic, Egyptian, Ethiopian, French Republican, Japanese, DateTimeConverter incl. cache and input-type behavior), models (SavedConversion, note behavior, user serialization), test-runner services
- **Dusk browser tests** (`tests/Browser/`) — landing page, saved conversions, note management, toast notifications (with page objects under `Pages/`)
- **Vitest component tests** (`resources/js/**/*.test.tsx`) — React components (results grid, quick actions, search filter, modals, saved-conversions section), guest storage, seasonal background, theme, landing/auth pages; run with `npm run test:run`

### Interactive Test Runner
`php artisan test:runner` (see the [README](../README.md#interactive-test-runner) for configuration). Backed by `TestDiscoveryService`, `TestExecutionService`, `ProgressTrackingService`, `ErrorRecoveryService`, `MarkdownReportService`, with queue-based execution via `ExecuteTestSuiteJob` and events (`TestExecutionStarted`, `TestCompleted`, `TestExecutionCompleted`) driving live progress and Markdown reports.

## Build & Deployment Architecture

### Development Environment
Laravel Sail (Docker) with MySQL; port mappings come from `.env` (`APP_PORT` default 8081, `VITE_PORT` default 5173, `FORWARD_DB_PORT` default 3307).

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

### Production Docker
Multi-stage `Dockerfile`:
1. **Composer stage** — production dependencies, optimized autoloader
2. **Node stage** — builds production Vite assets
3. **Runtime stage** — PHP 8.2-FPM (Alpine) + Nginx + Supervisord, copying artifacts from the builder stages

Supporting files: `docker/nginx.conf`, `docker/supervisord.conf`, `docker/entrypoint.sh`, `.dockerignore`.

```bash
docker build -t epochbridge:latest .
docker run -d --name epochbridge -p 80:80 \
  -e APP_KEY=... -e DB_HOST=... -e DB_DATABASE=... \
  -e DB_USERNAME=... -e DB_PASSWORD=... epochbridge:latest
```

### Traditional Deployment
```bash
npm run build
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize   # config, routes, views
```

## Security Architecture

### Transport & Headers
- **HTTPS forced in production**: `URL::forceScheme('https')` (`AppServiceProvider`) plus `ForceHttps` middleware on the web group
- **`SetSecurityHeaders` middleware** (web group, `app/Http/Middleware/SetSecurityHeaders.php`):
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - Content-Security-Policy: `default-src 'self'`; scripts/styles allow `'unsafe-inline'` (inline appearance bootstrap + React inline styles), everything else locked to same-origin; dev origins (Vite ports) allowlisted in local/testing only
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains` — sent only when the request actually arrived over TLS

### Authentication Security
- Bcrypt/Argon2 password hashing; session-based web guard; CSRF tokens on all frontend API calls (`X-CSRF-TOKEN` header from the page meta tag)
- Two-factor authentication (TOTP + recovery codes), password confirmation for sensitive settings
- Rate limiting on all auth and public API surfaces (see the table above)
- OAuth: PKCE, provider allowlist, encrypted token storage, unverified-email linking refused, provider error text never reflected

### Data Protection
- Input validation via Form Requests with payload-size bounds (`MaxPayloadBytes`), note length caps, and array-size caps
- Ownership checks on saved-conversion mutations (403 on mismatch)
- Eloquent parameterized queries; mass-assignment protection via `$fillable`
- XSS: React escaping; CSP as defense in depth

## Performance Considerations

- **Conversion caching**: results cached for 1 hour (`Cache::remember` on an MD5 key of input + type), split into `conversion_fast_*` and `conversion_additional_*` tiers. Time-precise input types (`unix`, `iso8601`) skip the cache entirely — every distinct value yields a distinct result, so cached entries would never be reused while the key space grows without bound.
- **Public API throttling**: `frontend-public` (60/min/IP) protects conversion and On This Day endpoints
- **Asset pipeline**: Vite production builds with tree-shaking; Tailwind CSS 4 purging
- **On This Day caching**: database cache-first lookups avoid repeat Wikimedia API calls; nightly scraper repair keeps entries fresh
- **Query hygiene**: pagination (20/page) on saved conversions; covering indexes on `(user_id, created_at)` and `created_at`

## Architecture Decision Records

### ADR-001: React/Inertia frontend (April 2026)
**Decision**: Migrate the presentation layer from Blade/Volt/Livewire/Flux/Alpine to React 19 + Inertia, keeping Laravel as the backend. **Status**: complete; the Livewire/Volt runtime was fully removed in September 2026 (`a144ac3`, `16c4d69`).
**Rationale**: a typed component ecosystem (TypeScript, Vitest + Testing Library), SPA-grade interactivity with server-side routing, and Inertia's first-class Laravel integration. *Supersedes the original TALL-stack and Livewire-Volt decisions (2025).*

### ADR-002: Calendar library selection (2025)
**Decision**: combine `fisharebest/ext-calendar` and `morilog/jalali`.
**Rationale**: comprehensive calendar coverage, accurate historical calculations, active maintenance.

### ADR-003: Adapter pattern for calendar services (2025)
**Decision**: adapter interface + DI binding for the French Republican calendar (initial implementation); extensible to other complex calendars.
**Rationale**: mockable for tests, swappable calculation strategies, per-instance caching without global state.

### ADR-004: Docker environments (2025)
**Decision**: Laravel Sail for development; multi-stage Docker (Nginx + PHP-FPM + Supervisord) for production.
**Rationale**: consistent onboarding, isolated dependencies, production-parity container.

### ADR-005: Session-backed frontend API instead of a token API (April 2026)
**Decision**: the React app consumes JSON routes in `routes/frontend.php` mounted on the `web` middleware group (session + CSRF), not a `routes/api.php` token surface.
**Rationale**: the API serves the first-party frontend only; session auth avoids token storage and keeps CSRF protection. See [api-endpoint-evaluation.md](api-endpoint-evaluation.md) for the original evaluation and its outcome note.
