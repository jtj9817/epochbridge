# TICKET: Complete React Frontend Migration Route Cutover

**Ticket ID**: EPOCH-2026-04-14-001  
**Type**: Task  
**Priority**: High  
**Status**: Done  
**Component**: Frontend Migration  
**Labels**: `react`, `inertia`, `migration`, `routes`

---

## Summary

Complete the React frontend migration by switching the main landing page (`/`) from Livewire/Volt to React/Inertia, fixing bundler configuration issues, and updating related tests.

---

## Background & Context

### Migration History

The EpochBridge frontend migration from TALL Stack (Tailwind, Alpine, Livewire, Laravel) to React/Inertia was executed across 8 phases (documented in `docs/react-frontend-migration-plan.md`):

| Phase | Description | Status |
|-------|-------------|--------|
| 0 | Baseline & Coexistence Setup | Complete |
| 1 | React + Inertia Foundation | Complete |
| 2 | Frontend API Contracts | Complete |
| 3 | React Public Shell & Landing Page | Complete |
| 4 | Guest/Auth Saves, Notes & Legacy Fallbacks | Complete |
| 5 | Modals, Toasts & Wikimedia On This Day UX | Complete |
| 6 | Authentication Pages | Complete |
| 7 | App Shell, Dashboard & Settings | Complete |
| 8 | Testing Infrastructure & Route Cutover | Complete |

**Total implementation**: 8 commits, +11,043/-172 lines, 129 files, 154 tests (88 PHP + 66 React)

### Gap Identified

Despite Phase 8 marking the migration as "complete" with `FRONTEND_REACT_ENABLED=true` as default, the actual route cutover for the main landing page was not executed:

```php
// routes/web.php (before fix)
Volt::route('/', 'landing')->name('home');  // Always Livewire, ignoring feature flag
```

The React landing page was only accessible at `/react`, not the main `/` route.

---

## Acceptance Criteria

- [x] Main landing page (`/`) serves React `LandingPage` component when `FRONTEND_REACT_ENABLED=true`
- [x] Main landing page (`/`) falls back to Volt `landing` component when `FRONTEND_REACT_ENABLED=false`
- [x] Test files (`*.test.tsx`) are excluded from production bundle
- [x] All React feature tests pass (40 tests)
- [x] All Vitest component tests pass (66 tests)
- [x] Assets rebuild successfully with updated configuration

---

## Technical Changes

### 1. Route Cutover (`routes/web.php`)

**Before:**
```php
if ($reactEnabled) {
    Route::get('/react-test', fn () => Inertia::render('Test'))->name('react.test');
    Route::get('/react', LandingController::class)->name('react.landing');
}

Volt::route('/', 'landing')->name('home');  // Always Livewire
```

**After:**
```php
if ($reactEnabled) {
    Route::get('/', LandingController::class)->name('home');  // React landing
    Route::get('/react-test', fn () => Inertia::render('Test'))->name('react.test');
} else {
    Volt::route('/', 'landing')->name('home');  // Livewire fallback
}
```

### 2. Vite Glob Pattern Fix (`resources/js/app.tsx`)

**Before:**
```tsx
const pages = import.meta.glob<ResolvedComponent>('./pages/**/*.tsx')
// Included test files (*.test.tsx) in production bundle
```

**After:**
```tsx
const pages = import.meta.glob<ResolvedComponent>([
  './pages/**/*.tsx',
  '!./pages/**/*.test.tsx',  // Exclude test files
])
```

### 3. Test Route Update (`tests/Feature/React/LandingPageTest.php`)

Updated test routes from `/react` to `/` to match the new route configuration.

---

## Verification Results

### Route Verification

```bash
$ curl -s http://localhost:8086/ | grep -o '"component":"[^"]*"'
"component":"LandingPage"
```

### Test Results

| Suite | Tests | Status |
|-------|-------|--------|
| PHP Feature (React) | 40 | Pass |
| Vitest Component | 66 | Pass |

### Build Verification

```
✓ built in 3.07s
# Test files no longer appear in build output
```

---

## Files Changed

| File | Change Type | Description |
|------|-------------|-------------|
| `routes/web.php` | Modified | Route cutover for `/` to React |
| `resources/js/app.tsx` | Modified | Exclude test files from bundle |
| `tests/Feature/React/LandingPageTest.php` | Modified | Update test route to `/` |

---

## Known Issues (Out of Scope)

### 1. Legacy Livewire Tests Failing

Two tests in `tests/Feature/Livewire/LandingPageTest.php` fail due to a **pre-existing bug** (not introduced by this migration):

- `it loads On This Day content and clears loading state`
- `it sets On This Day error when service returns an error`

**Root Cause**: Tests mock `OnThisDayService` but the Livewire component uses `OnThisDayCacheService`. This is a service class mismatch in the test file, not a React migration issue.

**Recommendation**: File separate ticket to fix or remove legacy Livewire tests.

### 2. Environment Port Mismatch

The `.env` file has inconsistent port configuration:

```ini
APP_URL=http://localhost:8080
APP_PORT=8086  # Actual Docker port mapping
GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback
```

**Impact**: OAuth callbacks may fail in development if using port 8080 URLs.

**Recommendation**: Update `APP_URL` and `GOOGLE_REDIRECT_URI` to use port `8086`.

### 3. Legacy Blade Files Remain

25 Livewire Blade files remain in `resources/views/livewire/`:

- `auth/*.blade.php` (7 files)
- `settings/*.blade.php` (6 files)
- `components/*.blade.php` (11 files)
- `landing.blade.php`

These serve as fallback when `FRONTEND_REACT_ENABLED=false` and can be removed after the migration stabilization period.

---

## Rollback Procedure

To revert to Livewire landing page:

```bash
# Option 1: Environment variable
echo "FRONTEND_REACT_ENABLED=false" >> .env
php artisan config:clear

# Option 2: Git revert (if committed)
git revert <commit-hash>
```

---

## Related Documentation

- `docs/react-frontend-migration-plan.md` - Full migration plan and architecture
- `20260414_react-migration_audit.md` - Phase-by-phase implementation audit
- `docs/architecture-overview.md` - System architecture reference

---

## Commit Reference

```
fix(react): complete landing page route cutover and bundle config

- Switch main landing page (/) from Volt to React when FRONTEND_REACT_ENABLED=true
- Exclude test files (*.test.tsx) from Vite production bundle
- Update LandingPageTest to use new route path
- Preserve Livewire fallback for feature flag disabled state
```
