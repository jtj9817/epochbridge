# Repository Guidelines

## Project Structure & Module Organization
EpochBridge runs on Laravel 12 with the TALL stack. Domain logic stays in `app/`—calendar conversions under `app/Services/*CalendarService.php` and interactive flows under `app/Livewire`. Views, Tailwind assets, and Vite entry points live in `resources/views`, `resources/css`, and `resources/js`. Routes are declared in `routes/web.php` (HTTP) and `routes/api.php` (API); database setup is in `database/migrations` and `database/seeders`. Tests live in `tests/` with Pest suites split into `Feature`/`Unit` and Dusk browser specs in `tests/Browser`. Reference deep dives in `docs/`.

## Build, Test, and Development Commands
- `composer install` / `npm install`: bootstrap PHP and JS dependencies.
- `./vendor/bin/sail up` brings up the Docker stack (MySQL, queue, Dusk); `./vendor/bin/sail down` stops it.
- `composer run dev` runs the app server, queue listener, Pail logs, and Vite watcher together.
- `npm run dev` serves Vite with HMR; `npm run build` emits production assets.
- `php artisan migrate --graceful [--seed]` applies database changes.

## Coding Style & Naming Conventions
Format PHP with `./vendor/bin/pint` (PSR-12, four-space indentation) before pushing. Service classes use descriptive calendar names, Livewire components stay in PascalCase, and Blade files use kebab-case. Tailwind utility classes live beside components in `resources/views/components`, and ES modules export camelCase symbols via Vite ESM imports.

## Testing Guidelines
Run the main suite with `php artisan test`; target suites via `--testsuite=Feature|Unit`. Launch browser coverage with `php artisan dusk` after Sail is running. Heavy suites can use `./vendor/bin/paratest --runner pest` for parallel execution. Name tests with Pest's `it('describes behavior')` style and keep fixtures under the matching feature folder.

## Commit & Pull Request Guidelines
Follow the repository pattern `type: summary` (`feat:`, `fix:`, `docs:`). Keep each commit focused and include migrations or compiled assets only when essential. PRs should note user-visible changes, list the checks you ran (`php artisan test`, `php artisan dusk`, etc.), and add UI screenshots when relevant. Tag reviewers responsible for the affected area and wait for CI to pass before merging.

## Configuration & Security Tips
Copy `.env.example` to `.env`, then set calendar keys and database credentials before `composer run dev`. Secrets stay out of git—use Sail overrides or Docker secrets instead. If working without Sail, align queue settings in `config/queue.php` and run `php artisan queue:listen --tries=1` locally.
