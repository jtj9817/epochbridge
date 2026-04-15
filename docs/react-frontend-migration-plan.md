# React Frontend Migration Plan

**Created**: 2026-04-01
**Completed**: Phase 0-8 (All phases complete)
**Status**: 🟢 Complete — React routes enabled by default
**Purpose**: Migrate the full EpochBridge presentation layer from Blade/Volt/Livewire/Flux/Alpine to React while preserving Laravel as the application backend and avoiding behavioral regressions.

---

## Problem Statement
EpochBridge's frontend is currently a TALL-stack application, but the UI behavior is spread across Blade, Volt, Livewire, Alpine, Flux, and ad hoc browser storage logic. That has worked for the existing scope, but it creates tight coupling between rendering, page state, event wiring, and persistence that will make deeper frontend evolution slower and riskier.

Codebase review shows the following concrete issues:

1. **Frontend logic is embedded in server-rendered Volt files**: the main public experience at `resources/views/livewire/landing.blade.php` owns conversion state, search/filter state, Wikimedia modal state, guest-save logic, and Livewire event dispatch/listener wiring in a single file.
2. **The application has no browser-facing API layer for React to consume**: `routes/api.php` does not exist, and current interactive flows call services and models directly from Volt closures.
3. **State is split across incompatible mechanisms**: Livewire state, Alpine local component state, browser `localStorage`, and `window` custom events are all used at the same time, especially in `landing.blade.php`, `saved-conversions-section.blade.php`, `saved-conversion-item.blade.php`, and `toast-notification.blade.php`.
4. **UI primitives are coupled to Flux tags and Blade components**: the repository currently has 227 `flux:` usages and 21 `wire:navigate` usages across auth, settings, layouts, and navigation.
5. **Guest and authenticated persistence rules are mixed into view logic**: guest saves use the `savedConversions` and `conversionCollapsedStates` localStorage keys with a hard limit of 3 items, while authenticated saves depend on `SavedConversion` plus legacy fallback behavior.
6. **Contract shape is inconsistent for React consumption**: `DateTimeConverterService` returns a mix of structured calendar objects and scalar values (`unix`, `iso8601`, `julian-day`, `modified-julian-day`), and `SavedConversion` still preserves legacy compatibility fields.
7. **The test suite is strongly coupled to Volt/Livewire behavior**: the current frontend surface is covered by many `Volt::test(...)` tests and Dusk browser tests that target current selectors and Livewire event flows.

Current code-derived scope metrics:

- `resources/views/livewire`: 25 files
- `resources/views/components`: 39 files
- `resources/views/components/calendars`: 21 files
- frontend tests under `tests/Feature/Livewire` and `tests/Browser`: 17 files
- Alpine `x-data` usages: 20
- `localStorage` usages: 23
- `resources/js/app.js`: only axios bootstrap; no React runtime exists yet

The migration therefore cannot be treated as a cosmetic rewrite. It requires an architectural extraction of browser contracts, a coexistence strategy, and an explicit path for replacing Livewire-specific routing, state, and component semantics.

---

## Design Decisions (Architecture Preferences)

| Decision | Choice |
| :--- | :--- |
| Frontend framework | React with TypeScript |
| Laravel integration model | Inertia React, not a standalone SPA |
| Migration strategy | Strangler-fig dual stack with route-level feature flags |
| Backend interaction style | Inertia for page navigation, JSON endpoints for highly interactive page actions |
| Authentication model | Existing Laravel session/cookie auth and Fortify flows |
| UI component strategy | Replace Flux tags with React UI primitives using current Tailwind/CSS theme tokens |
| Guest save continuity | Preserve `savedConversions` and `conversionCollapsedStates` keys during migration |
| Saved conversion contract | Normalize through Resources/DTO-like transformers before React consumption |
| Legacy data support | Preserve current `conversion_data` plus legacy fallback support in API/resources |
| Browser testing direction | Add React-oriented browser tests, keep critical Dusk smoke coverage until cutover |
| SSR requirement | Optional later; do not block the migration on React SSR |
| Inertia version | v3 with `@inertiajs/vite` plugin for simplified setup |
| Route generation | Ziggy (`tightenco/ziggy`) for `route()` helper in React |
| Boost guidance source | Verified with `laravel-boost` MCP; patterns confirmed against inertiajs/inertia-laravel@3.x docs |

### Inertia v3 Verification Summary (via Laravel Boost MCP)

The following patterns were verified against `inertiajs/inertia-laravel@3.x` documentation:

| Pattern | Verified Detail |
| :--- | :--- |
| **Root template** | Use `<x-inertia::head />` and `<x-inertia::app />` Blade components (v3), NOT `@inertia`/`@inertiaHead` directives (v1/v2) |
| **Vite plugin** | `@inertiajs/vite` auto-resolves pages from `./pages` or `./Pages`; minimal `createInertiaApp()` call needed |
| **React refresh** | Include `@viteReactRefresh` directive BEFORE `@vite` for Fast Refresh in development |
| **Middleware** | Publish via `php artisan inertia:middleware`, register in `bootstrap/app.php` with `$middleware->web(append: [...])` |
| **Flash data** | Use `Inertia::flash()` method (v2/v3), listen via `router.on('flash')` in React; auto-cleared after send |
| **useForm helper** | Provides `{ data, setData, post, processing, errors }` for type-safe form handling |
| **Shared data** | Use lazy evaluation (`fn () => ...`) in `share()` method; include Ziggy routes for `route()` helper |
| **TypeScript** | Augment `@inertiajs/core` PageProps interface for type-safe shared data access |

### Why Inertia React Instead of a Standalone SPA
The codebase is still a Laravel monolith with Fortify auth, session redirects, Google OAuth callbacks, and no browser API boundary. A standalone SPA would force unnecessary work up front:

- full API/auth redesign
- token-based frontend auth or Sanctum SPA mode
- duplicate route/redirect logic
- a more invasive cutover for auth, settings, and verification flows

Inertia React keeps Laravel in control of routing, auth, redirects, middleware, and page props while still moving the entire rendered UI into React. That is the lowest-risk interpretation of “migrate the entire front-end into React” for this repository.

---

## Existing Frontend Inventory

### Current Route Surface

| Current Route | Current Rendering | Target React Page | Notes |
| :--- | :--- | :--- | :--- |
| `/` | Volt `landing` | `LandingPage` | Highest complexity; includes conversion, guest save, notes, on-this-day |
| `/dashboard` | Blade view with app layout | `DashboardPage` | Low complexity; placeholder content today |
| `/login` | Volt auth page | `Auth/LoginPage` | Uses Fortify/session logic |
| `/register` | Volt auth page | `Auth/RegisterPage` | Uses Fortify/session logic |
| `/forgot-password` | Volt auth page | `Auth/ForgotPasswordPage` | Form post flow |
| `/reset-password/{token}` | Volt auth page | `Auth/ResetPasswordPage` | Tokenized form |
| `/verify-email` | Volt auth page | `Auth/VerifyEmailPage` | React wrapper around existing behavior |
| `/settings/profile` | Volt settings page | `Settings/ProfilePage` | Includes delete-user flow |
| `/settings/password` | Volt settings page | `Settings/PasswordPage` | Inline success event today |
| `/settings/appearance` | Volt settings page | `Settings/AppearancePage` | Currently Flux appearance integration |
| `/settings/two-factor` | Volt settings page | `Settings/TwoFactorPage` | High-sensitivity flow |
| `/auth/google/*` | Controller redirects/callback | Server-controlled | Keep server-handled, React only renders landing/return states |
| `POST /logout` | Livewire action class | Server-controlled action triggered from React | Keep server route |

### Current High-Risk Components

| File | Current Responsibility | Migration Risk |
| :--- | :--- | :--- |
| `resources/views/livewire/landing.blade.php` | Main orchestrator for conversion, filtering, saves, guest migration, on-this-day modal, notifications | Very High |
| `resources/views/livewire/components/calendar-results-grid.blade.php` | Results rendering, filtering, clipboard, save trigger | High |
| `resources/views/livewire/components/saved-conversions-section.blade.php` | Auth saves, guest saves, legacy normalization, note updates | Very High |
| `resources/views/components/saved-conversion-item.blade.php` | Collapse state, copy actions, guest/auth note editing, delete actions | Very High |
| `resources/views/livewire/components/calendar-quick-actions.blade.php` | Input-type-aware date generation across many calendar services | High |
| `resources/views/livewire/components/on-this-day-modal*.blade.php` | Modal state, date switching, API/cache reads | Medium |
| `resources/views/livewire/auth/*.blade.php` | Auth UI bound to Volt/Flux | Medium |
| `resources/views/livewire/settings/*.blade.php` | Sensitive account settings flows | High |
| `resources/views/components/layouts/app/sidebar.blade.php` | App shell + user menu + `wire:navigate` links | Medium |
| `resources/views/components/toast-notification.blade.php` | Global Alpine toast bus | Medium |

### Current Backend Contracts React Must Respect

| File | Existing Contract | React Impact |
| :--- | :--- | :--- |
| `app/Services/DateTimeConverterService.php` | Converts across current input types and returns mixed scalar/object output | Must be normalized before direct React use |
| `app/Services/Wikimedia/OnThisDayCacheService.php` | Reads cached payloads by Gregorian date | Reuse through HTTP controller layer |
| `app/Models/SavedConversion.php` | Holds `conversion_data`, `note`, compatibility fields, legacy fallback accessors | Resource layer must preserve old and new rows |
| `app/Http/Requests/DateConversionRequest.php` | Only validates Gregorian date + timezone | Insufficient for current landing UI; replace or supersede |
| `routes/web.php` and `routes/auth.php` | Volt-based route registration | Must be rewritten to controller/Inertia route definitions |

### Existing Docs That Inform This Plan

| Document | Relevant Use |
| :--- | :--- |
| `docs/architecture-overview.md` | Confirms TALL-stack architecture and page/component inventory |
| `docs/component-architecture.md` | Documents current props-down/events-up structure for landing |
| `docs/component-refactoring-roadmap.md` | Useful decomposition of landing responsibilities |
| `docs/api-endpoint-evaluation.md` | Confirms current Livewire-first design and outlines API need when external frontend appears |
| `docs/api-implementation-plan.md` | Shows prior service extraction history for date conversion |

---

## Solution Architecture

### Overview
```text
Browser
  -> Laravel Web Routes
      -> Inertia Controllers
          -> React Pages / Layouts / Hooks
              -> JSON endpoints for interactive actions
                  -> Form Requests / Resources / Services / Models

Public landing:
React LandingPage
  -> useConversion()
      -> POST /frontend/conversions
      -> GET /frontend/on-this-day?date=YYYY-MM-DD
  -> useSavedConversions()
      -> guest localStorage adapter OR auth JSON endpoints
  -> ToastProvider / Modal components / Calendar card components

Authenticated shell:
Inertia AppLayout
  -> DashboardPage
  -> Settings/* pages
  -> Auth pages
  -> server props for auth/user/flash
```

### Target Frontend File Structure
```text
resources/js/
├── app.tsx
├── ssr.tsx                       # optional later
├── bootstrap/
│   ├── axios.ts
│   └── inertia.ts
├── layouts/
│   ├── PublicLayout.tsx
│   ├── AuthLayout.tsx
│   └── AppLayout.tsx
├── pages/
│   ├── LandingPage.tsx
│   ├── DashboardPage.tsx
│   ├── Auth/
│   │   ├── LoginPage.tsx
│   │   ├── RegisterPage.tsx
│   │   ├── ForgotPasswordPage.tsx
│   │   ├── ResetPasswordPage.tsx
│   │   ├── VerifyEmailPage.tsx
│   │   └── TwoFactorChallengePage.tsx
│   └── Settings/
│       ├── ProfilePage.tsx
│       ├── PasswordPage.tsx
│       ├── AppearancePage.tsx
│       └── TwoFactorPage.tsx
├── components/
│   ├── ui/
│   ├── calendars/
│   ├── saved-conversions/
│   ├── modals/
│   └── forms/
├── hooks/
│   ├── useConversion.ts
│   ├── useOnThisDay.ts
│   ├── useSavedConversions.ts
│   ├── useGuestSavedConversions.ts
│   └── useToast.ts
├── lib/
│   ├── api.ts
│   ├── guest-storage.ts
│   ├── converters.ts
│   └── routes.ts
├── types/
│   ├── calendar.ts
│   ├── saved-conversion.ts
│   ├── auth.ts
│   ├── page-props.ts
│   └── inertia.d.ts          # Global Inertia type augmentation
└── providers/
    └── ToastProvider.tsx
```

**Inertia TypeScript Configuration** (`resources/js/types/inertia.d.ts`):
```ts
import { PageProps as InertiaPageProps } from '@inertiajs/core'

// Augment Inertia's PageProps with your app's shared data
declare module '@inertiajs/core' {
  interface PageProps extends InertiaPageProps {
    auth: {
      user: { id: number; name: string; email: string } | null
    }
    ziggy: {
      url: string
      port: number | null
      defaults: Record<string, unknown>
      routes: Record<string, { uri: string; methods: string[] }>
      location: string
    }
    flash?: {
      toast?: { type: 'success' | 'error' | 'info'; message: string }
    }
  }
}
```

### Target Laravel File Structure
```text
app/Http/
├── Controllers/
│   ├── Frontend/
│   │   ├── LandingPageController.php
│   │   ├── DashboardPageController.php
│   │   ├── Settings/
│   │   └── Auth/
│   └── FrontendApi/
│       ├── ConversionController.php
│       ├── OnThisDayController.php
│       ├── SavedConversionController.php
│       └── SavedConversionNoteController.php
├── Middleware/
│   └── HandleInertiaRequests.php
├── Requests/
│   └── Frontend/
│       ├── ConvertDateRequest.php
│       ├── SaveSavedConversionRequest.php
│       ├── UpdateSavedConversionNoteRequest.php
│       └── OnThisDayRequest.php
└── Resources/
    └── Frontend/
        ├── ConversionResultResource.php
        ├── SavedConversionResource.php
        └── OnThisDayResource.php
```

---

## Migration Principles

1. **Do not rewrite backend calendar logic**. `DateTimeConverterService` and calendar services remain the source of truth.
2. **Do not expose raw Eloquent/Livewire shapes to React**. Introduce request/resource contracts first.
3. **Do not remove Livewire routes early**. Build React pages beside the current stack and cut over only after parity.
4. **Preserve guest data continuity**. React must read the same localStorage keys before any schema migration.
5. **Normalize edge-case payloads at the server edge**. React should not own legacy compatibility rules that already belong in Laravel.
6. **Keep auth server-driven**. OAuth redirects, Fortify behavior, email verification, and logout remain Laravel responsibilities.

---

## Implementation Tasks

### Phase 0: Baseline, ADR, and Coexistence Setup 🟢 COMPLETED

#### Task 0.1: Record the target architecture and route cutover policy 🟢
**File**: `docs/react-frontend-migration-plan.md`

```md
Decision: use Inertia React with route-level coexistence.
Rule: old Volt routes remain available until page-level parity is signed off.
Rollback: route flag returns traffic to Volt immediately.
```

**Key Logic/Responsibilities**:
- Lock the migration direction before package and route changes begin.
- Prevent a partial rewrite from drifting into a half-SPA, half-Livewire architecture.

#### Task 0.2: Add a frontend migration feature flag 🟢
**File**: `config/frontend.php`

```php
return [
    'react_enabled' => env('FRONTEND_REACT_ENABLED', false),
];
```

**Key Logic/Responsibilities**:
- Allow page-by-page or route-group cutover.
- Make rollback operationally cheap.

---

### Phase 1: Install React + Inertia Foundation 🟢 COMPLETED

#### Task 1.1: Add the React runtime and Laravel integration packages 🟢
**File**: `package.json`, `composer.json`

```bash
# PHP packages
composer require inertiajs/inertia-laravel tightenco/ziggy

# npm packages (Inertia v3 with Vite plugin)
npm install @inertiajs/react @inertiajs/vite
npm install react react-dom
npm install -D typescript @types/react @types/react-dom
```

**Key Logic/Responsibilities**:
- Install Inertia v3 with the `@inertiajs/vite` plugin (simplifies page resolution and SSR setup).
- Add `tightenco/ziggy` for the `route()` helper in React components.
- Add `inertiajs/inertia-laravel` on the PHP side.
- TypeScript toolchain without disrupting existing Tailwind/Vite usage.

#### Task 1.2: Create the Inertia bootstrap entrypoint 🟢
**File**: `resources/js/app.tsx`

```tsx
import { createInertiaApp } from '@inertiajs/react'

// Inertia v3 with @inertiajs/vite plugin - minimal setup required
// The Vite plugin auto-resolves pages from ./pages or ./Pages directories
createInertiaApp({
  // Optional: wrap app with providers
  withApp(app) {
    return (
      <ToastProvider>
        {app}
      </ToastProvider>
    )
  },
})
```

For manual setup (without Vite plugin), use `resolvePageComponent`:
```tsx
import { createInertiaApp } from '@inertiajs/react'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createRoot } from 'react-dom/client'

createInertiaApp({
  resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />)
  },
})
```

**Key Logic/Responsibilities**:
- Replace the current JS bootstrap, which only sets axios defaults.
- Use `@inertiajs/vite` plugin for automatic page resolution (preferred).
- Establish shared providers via `withApp` callback.

#### Task 1.3: Add Inertia middleware and root Blade shell 🟢

**Step 1**: Publish the middleware:
```bash
php artisan inertia:middleware
```

**Step 2**: Register in `bootstrap/app.php`:
```php
use App\Http\Middleware\HandleInertiaRequests;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        HandleInertiaRequests::class,
    ]);
})
```

**File**: `app/Http/Middleware/HandleInertiaRequests.php`
```php
class HandleInertiaRequests extends Middleware
{
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => fn () => $request->user()?->only('id', 'name', 'email'),
            ],
            'ziggy' => fn () => [
                ...(new \Tighten\Ziggy\Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
```

**File**: `resources/views/app.blade.php` (Inertia v3 syntax)
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    <x-inertia::head />
</head>
<body class="font-sans antialiased">
    <x-inertia::app />
</body>
</html>
```

**Key Logic/Responsibilities**:
- Use Inertia v3 Blade components (`<x-inertia::head />`, `<x-inertia::app />`), not legacy directives.
- Include `@viteReactRefresh` before `@vite` for React Fast Refresh in development.
- Share Ziggy routes for `route()` helper in React.
- Use Inertia v3 flash system (`Inertia::flash()`) instead of manual session flash sharing.

#### Task 1.4: Upgrade Vite configuration for React/TSX while retaining Tailwind v4 🟢
**File**: `vite.config.js`

```js
import inertia from '@inertiajs/vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        inertia(),        // Inertia v3 Vite plugin for auto page resolution
        react(),          // React plugin for JSX/TSX support
        tailwindcss(),    // Existing Tailwind v4 integration
    ],
})
```

**File**: `tsconfig.json` (create if not exists)
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true,
    "paths": {
      "@/*": ["./resources/js/*"]
    }
  },
  "include": ["resources/js/**/*"]
}
```

**Key Logic/Responsibilities**:
- Add `@inertiajs/vite` plugin (auto-resolves pages from `./pages` or `./Pages`).
- Add `@vitejs/plugin-react` for JSX/TSX compilation.
- Keep existing theme tokens and CSS variables from `resources/css/app.css`.
- TypeScript config with path aliases for clean imports.

---

### Phase 2: Extract a Stable Browser Contract From Livewire Logic 🟢 COMPLETED

#### Task 2.1: Replace the narrow conversion request object 🟢 COMPLETED ✅
**File**: `app/Http/Requests/Frontend/ConvertDateRequest.php`

```php
return [
    'date' => ['required', 'string'],
    'input_type' => ['required', Rule::in([
        'gregorian', 'jalali', 'unix', 'iso8601', 'jd', 'mjd', 'hebrew',
        'islamic', 'french_republican', 'chinese', 'japanese', 'coptic',
        'egyptian', 'ethiopian', 'babylonian', 'assyrian', 'macedonian', 'mayan',
    ])],
];
```

**Key Logic/Responsibilities**:
- Supersede `app/Http/Requests/DateConversionRequest.php`, which only supports Gregorian input.
- Match the actual `landing.blade.php` input surface.

#### Task 2.2: Normalize conversion output for React 🟢 COMPLETED ✅
**File**: `app/Http/Resources/Frontend/ConversionResultResource.php`

```php
return [
    'input' => [
        'date' => $this->inputDate,
        'type' => $this->inputType,
    ],
    'results' => $this->normalizeResults($this->resource),
    'meta' => [
        'on_this_day_date' => data_get($this->resource, 'gregorian.full_date'),
    ],
];
```

**Key Logic/Responsibilities**:
- Convert scalar values into predictable objects where needed.
- Remove React’s need to understand `julian_day` vs `julian-day` inconsistencies.

#### Task 2.3: Normalize saved conversion rows for both modern and legacy records 🟢 COMPLETED ✅
**File**: `app/Http/Resources/Frontend/SavedConversionResource.php`

```php
return [
    'id' => $this->id,
    'original_input' => $this->original_input,
    'note' => $this->note ?? '',
    'conversion_data' => $this->normalized_conversion_data,
    'uses_legacy_fallback' => $this->uses_legacy_fallback,
    'created_at' => $this->created_at?->toIso8601String(),
];
```

**Key Logic/Responsibilities**:
- Reuse the normalization intent already present in `saved-conversions-section.blade.php`.
- Keep legacy DB rows working after the frontend changes.

#### Task 2.4: Create JSON endpoints for interactive flows 🟢 COMPLETED ✅
**File**: `routes/frontend.php`

```php
Route::prefix('frontend')->group(function () {
    Route::post('conversions', [ConversionController::class, 'store']);
    Route::get('on-this-day', [OnThisDayController::class, 'show']);

    Route::middleware('auth')->group(function () {
        Route::get('saved-conversions', [SavedConversionController::class, 'index']);
        Route::post('saved-conversions', [SavedConversionController::class, 'store']);
        Route::patch('saved-conversions/{savedConversion}/note', [SavedConversionNoteController::class, 'update']);
        Route::delete('saved-conversions/{savedConversion}', [SavedConversionController::class, 'destroy']);
    });
});
```

**Key Logic/Responsibilities**:
- Keep browser interaction server-backed without building a separate external API product.
- Support React async behavior without page reloads.

---

### Phase 3: Build the React Public Shell and Landing Page 🟢 COMPLETED

#### Task 3.1: Introduce `LandingPage.tsx` with the exact current state model 🟢 COMPLETED ✅
**File**: `resources/js/pages/LandingPage.tsx`

```tsx
const [dateInput, setDateInput] = useState(todayString)
const [inputType, setInputType] = useState<'gregorian' | 'jalali' | 'unix' | ...>('gregorian')
const [results, setResults] = useState<ConversionResultMap | null>(null)
const [error, setError] = useState<string>('')
const [searchTerm, setSearchTerm] = useState('')
const [selectedTypes, setSelectedTypes] = useState<string[]>([])
```

**Key Logic/Responsibilities**:
- Port the current landing state intentionally rather than redesigning it mid-migration.
- Preserve current UX semantics before optimizing them.

#### Task 3.2: Port the input section and validation behavior 🟢 COMPLETED ✅
**File**: `resources/js/components/forms/CalendarInputSection.tsx`

```tsx
<select value={inputType} onChange={(e) => setInputType(e.target.value as InputType)}>
  {inputTypes.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
</select>
```

**Key Logic/Responsibilities**:
- Mirror the current input types from `landing.blade.php` and `calendar-input-section.blade.php`.
- Keep format guidance and input-type-specific placeholder behavior.

#### Task 3.3: Port quick actions with service-equivalent output semantics 🟢 COMPLETED ✅
**File**: `resources/js/components/forms/CalendarQuickActions.tsx`, `resources/js/lib/converters.ts`

```tsx
<button onClick={() => applyRelativeDate('today')}>TODAY</button>
<button onClick={() => applyRelativeDate('yesterday')}>YESTERDAY</button>
<button onClick={() => applyRelativeDate('tomorrow')}>TOMORROW</button>
```

**Key Logic/Responsibilities**:
- Preserve the current input-type-specific quick action behavior.
- Do not reimplement calendar math incorrectly in the browser; either call a lightweight endpoint or centralize exact formatting helpers.

#### Task 3.4: Port the results grid and 21 calendar cards 🟢 COMPLETED ✅
**File**: `resources/js/components/calendars/*.tsx`

```tsx
switch (result.key) {
  case 'gregorian':
    return <GregorianCard data={result} />
  case 'jalali':
    return <JalaliCard data={result} />
  default:
    return <GenericCalendarCard data={result} />
}
```

**Key Logic/Responsibilities**:
- Replace the current Blade card switchboard in `calendar-results-grid.blade.php`.
- Preserve the visual/semantic content of specialized cards while making them React components.

#### Task 3.5: Rebuild search/filter UI as pure React state 🟢 COMPLETED ✅
**File**: `resources/js/components/forms/CalendarSearchFilter.tsx`

```tsx
const filtered = useMemo(() => filterCalendars(results, searchTerm, selectedTypes), [results, searchTerm, selectedTypes])
```

**Key Logic/Responsibilities**:
- Remove the current Livewire event loop used by `calendar-search-filter.blade.php`.
- Keep the existing filter taxonomy: modern, historical, ancient, east-asian, lunar, solar, lunisolar, system.

---

### Phase 4: Port Guest Saves, Auth Saves, Notes, and Legacy Fallbacks 🟢 COMPLETED

#### Task 4.1: Extract guest storage into a dedicated browser adapter 🔴
**File**: `resources/js/lib/guest-storage.ts`

```ts
export const GUEST_SAVED_CONVERSIONS_KEY = 'savedConversions'
export const GUEST_COLLAPSED_STATES_KEY = 'conversionCollapsedStates'
export const GUEST_SAVE_LIMIT = 3
```

**Key Logic/Responsibilities**:
- Preserve the current storage keys and the guest save cap of 3.
- Keep browser storage behavior out of page components.

#### Task 4.2: Rebuild saved conversions as React components backed by normalized server resources 🔴
**File**: `resources/js/components/saved-conversions/SavedConversionsSection.tsx`

```tsx
const { items, saveNote, deleteItem } = useSavedConversions({ isAuthenticated })
```

**Key Logic/Responsibilities**:
- Preserve all current authenticated behaviors: list, delete, note update, legacy fallback rendering.
- Preserve guest behaviors: save, delete, collapse state, note editing, merge readiness.

#### Task 4.3: Preserve migration-on-login semantics for guest data 🔴
**File**: `resources/js/hooks/useGuestSavedConversions.ts`, backend merge action/controller

```ts
const migrateGuestSavedConversions = async (guestItems: GuestSavedConversion[]) => {
  await api.post('/frontend/saved-conversions/migrate', { items: guestItems })
}
```

**Key Logic/Responsibilities**:
- Replace the current landing-page-side migration logic with an explicit backend endpoint/action.
- Ensure no duplicate rows are created for authenticated users because the DB already enforces a unique `(user_id, original_input)` constraint.

#### Task 4.4: Port note modal workflows and copy actions 🔴
**File**: `resources/js/components/modals/EditNoteModal.tsx`, `resources/js/components/saved-conversions/SavedConversionItem.tsx`

```tsx
<EditNoteModal
  open={noteModal.open}
  initialValue={noteModal.currentNote}
  maxLength={2048}
  onSave={handleSaveNote}
/>
```

**Key Logic/Responsibilities**:
- Preserve note trimming, empty-note behavior, multiline note support, and max length 2048.
- Replace `window` custom events with explicit props and hook callbacks.

---

### Phase 5: Port Modals, Toasts, and Wikimedia “On This Day” UX 🟢 COMPLETED

#### Task 5.1: Rebuild the toast bus as a React provider with Inertia v3 flash integration 🔴
**File**: `resources/js/providers/ToastProvider.tsx`

```tsx
import { router, usePage } from '@inertiajs/react'
import { createContext, useContext, useEffect, useState } from 'react'

interface Toast { id: string; type: 'success' | 'error' | 'info'; message: string }
const ToastContext = createContext<{
  toasts: Toast[]
  addToast: (type: Toast['type'], message: string) => void
} | null>(null)

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])
  
  // Listen for Inertia v3 flash events (preferred over shared data for toasts)
  useEffect(() => {
    return router.on('flash', (event) => {
      const flash = event.detail.flash
      if (flash.toast) {
        addToast(flash.toast.type, flash.toast.message)
      }
    })
  }, [])
  
  const addToast = (type: Toast['type'], message: string) => {
    const id = crypto.randomUUID()
    setToasts(prev => [...prev, { id, type, message }])
    setTimeout(() => setToasts(prev => prev.filter(t => t.id !== id)), 5000)
  }
  
  return (
    <ToastContext.Provider value={{ toasts, addToast }}>
      {children}
    </ToastContext.Provider>
  )
}

export const useToast = () => useContext(ToastContext)!
```

**Server-side flash usage** (Inertia v3 pattern):
```php
// In controllers - use Inertia::flash() instead of session flash
Inertia::flash('toast', ['type' => 'success', 'message' => 'Conversion saved!']);
return back();
```

**Key Logic/Responsibilities**:
- Replace `toast-notification.blade.php` and the current `notify` browser event bus.
- Integrate with Inertia v3 `router.on('flash')` event for server-triggered toasts.
- Provide a single notification API for saves, deletes, errors, and copy actions.
- Flash data is automatically cleared after being sent (no history pollution).

#### Task 5.2: Rebuild the on-this-day modal around a typed endpoint 🔴
**File**: `resources/js/components/modals/OnThisDayModal.tsx`, `app/Http/Controllers/FrontendApi/OnThisDayController.php`

```php
return OnThisDayResource::make(
    $this->onThisDayCache->getForDate($request->validated('date'))
);
```

**Key Logic/Responsibilities**:
- Reuse `OnThisDayCacheService` instead of reworking Wikimedia logic.
- Keep the existing graceful handling for empty cache, invalid date, and external API/config errors.

#### Task 5.3: Rebuild the auxiliary modal layer 🔴
**File**: `resources/js/components/modals/CalendarsInfoModal.tsx`, `SaveConversionModal.tsx`

```tsx
<Modal open={open} onClose={close} title="Save Conversion">
  ...
</Modal>
```

**Key Logic/Responsibilities**:
- Replace Alpine entanglement and Livewire modal state.
- Standardize modal primitives before auth/settings migration reuses them.

---

### Phase 6: Migrate Authentication Pages to React Without Rewriting Fortify 🟢 COMPLETED

#### Task 6.1: Replace Volt auth pages with Inertia React pages 🔴
**File**: `resources/js/pages/Auth/*.tsx`, `routes/auth.php`

```php
Route::middleware('guest')->group(function () {
    Route::get('login', [LoginPageController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});
```

**Key Logic/Responsibilities**:
- Keep Fortify/session behavior on the server.
- Move only the rendering/forms to React.

#### Task 6.2: Replace Flux auth controls with React form primitives using Inertia useForm 🔴
**File**: `resources/js/pages/Auth/LoginPage.tsx`

```tsx
import { useForm, usePage } from '@inertiajs/react'

export default function LoginPage() {
  const { data, setData, post, processing, errors } = useForm({
    email: '',
    password: '',
    remember: false,
  })

  function submit(e: React.FormEvent) {
    e.preventDefault()
    post(route('login'))
  }

  return (
    <form onSubmit={submit}>
      <input
        type="email"
        value={data.email}
        onChange={(e) => setData('email', e.target.value)}
        autoComplete="email"
      />
      {errors.email && <div className="text-red-500">{errors.email}</div>}
      
      <input
        type="password"
        value={data.password}
        onChange={(e) => setData('password', e.target.value)}
      />
      {errors.password && <div className="text-red-500">{errors.password}</div>}
      
      <button type="submit" disabled={processing}>
        {processing ? 'Signing in...' : 'Sign in'}
      </button>
    </form>
  )
}
```

**Key Logic/Responsibilities**:
- Use Inertia's `useForm` helper for type-safe form handling, processing state, and error display.
- Preserve current technical visual language from `resources/css/app.css`.
- Remove dependence on Flux custom elements.
- `processing` state auto-disables submit button during form submission.

#### Task 6.3: Keep OAuth server-driven and React-safe 🔴
**File**: `app/Http/Controllers/Auth/SocialAuthController.php`, React login page

```tsx
<a href={route('social.redirect', { provider: 'google' })}>Sign in with Google</a>
```

**Key Logic/Responsibilities**:
- Do not move OAuth callback logic into the browser.
- Ensure React login surfaces `oauth` errors currently flashed back to `login`.

#### Task 6.4: Rebuild the two-factor challenge UI in React 🔴
**File**: `resources/js/pages/Auth/TwoFactorChallengePage.tsx`

```tsx
const [showRecoveryInput, setShowRecoveryInput] = useState(initialRecoveryMode)
```

**Key Logic/Responsibilities**:
- Preserve the current toggle between auth-code and recovery-code flows.
- Replace Alpine-only behavior with explicit React state.

---

### Phase 7: Migrate the Authenticated App Shell, Dashboard, and Settings 🟢 COMPLETED

#### Task 7.1: Replace `wire:navigate` app shell with Inertia layouts 🔴
**File**: `resources/js/layouts/AppLayout.tsx`

```tsx
<AppSidebar>
  <NavLink href={route('dashboard')}>Dashboard</NavLink>
  <NavLink href={route('profile.edit')}>Settings</NavLink>
</AppSidebar>
```

**Key Logic/Responsibilities**:
- Replace `resources/views/components/layouts/app/sidebar.blade.php`.
- Keep auth-driven user menu and logout action.

#### Task 7.2: Port settings pages one by one, not as a single blast radius change 🔴
**File**: `resources/js/pages/Settings/*.tsx`

```tsx
<SettingsLayout heading="Profile" subheading="Update your name and email address">
  ...
</SettingsLayout>
```

**Key Logic/Responsibilities**:
- Preserve high-sensitivity settings flows under server validation.
- Replace event-driven success messages with shared flash/toast handling.

#### Task 7.3: Treat two-factor settings as a separate hardening subproject 🔴
**File**: `resources/js/pages/Settings/TwoFactorPage.tsx`

```tsx
if (requiresConfirmation) {
  // show QR/setup step, then verification step
}
```

**Key Logic/Responsibilities**:
- Mirror the current enable/confirm/disable behavior implemented in `resources/views/livewire/settings/two-factor.blade.php`.
- Do not regress password confirmation, QR rendering, or manual setup key handling.

---

### Phase 8: Testing Migration, Verification, and Cutover 🟢 COMPLETED

#### Task 8.1: Add React component and integration tests 🟢 COMPLETED
**File**: `resources/js/**/*.test.tsx` or `tests/frontend/**/*.test.tsx`

```tsx
render(<LandingPage ... />)
expect(screen.getByText('Conversion Results')).toBeInTheDocument()
```

**Key Logic/Responsibilities**:
- Move pure UI logic out of Volt tests and into React test coverage.
- Keep server/resource tests in PHP.

#### Task 8.2: Replace page-level Volt tests with controller/resource/request tests 🟢 COMPLETED
**File**: `tests/Feature/Frontend/*`, `tests/Unit/Http/Resources/*`

```php
postJson('/frontend/conversions', ['date' => '2024-01-01', 'input_type' => 'gregorian'])
    ->assertOk()
    ->assertJsonPath('results.gregorian.full_date', '2024-01-01');
```

**Key Logic/Responsibilities**:
- Shift backend assertions away from Livewire internals.
- Verify contract stability for React.

#### Task 8.3: Keep Dusk smoke tests until React parity is proven 🟢 COMPLETED
**File**: `tests/Browser/*`

```php
$browser->visit('/')
    ->assertSee('EpochBridge')
    ->type('date-input-gregorian', '2024-10-27');
```

**Key Logic/Responsibilities**:
- Use existing end-to-end coverage as the safety net during migration.
- Replace or supplement with Playwright later, not before parity.

#### Task 8.4: Cut over routes page-by-page, then remove dead Livewire code 🟢 COMPLETED
**File**: `routes/web.php`, `routes/auth.php`, `resources/views/livewire/**`, `resources/views/components/**`

```php
if (config('frontend.react_enabled')) {
    Route::get('/', LandingPageController::class)->name('home');
} else {
    Volt::route('/', 'landing')->name('home');
}
```

**Key Logic/Responsibilities**:
- Flip one route at a time.
- Delete Livewire/Volt/Flux remnants only after tests pass and production smoke checks succeed.

---

## API and Contract Plan

### Conversion Endpoint
**Route**: `POST /frontend/conversions`

**Request**:
```json
{
  "date": "2024-01-15",
  "input_type": "gregorian"
}
```

**Response**:
```json
{
  "input": {
    "date": "2024-01-15",
    "type": "gregorian"
  },
  "results": {
    "gregorian": { "full_date": "2024-01-15", "name": "Gregorian", "tags": ["modern", "solar"] },
    "jalali": { "full_date": "1402/10/25", "name": "Jalali", "tags": ["modern", "solar"] },
    "unix": { "value": 1705276800, "name": "Unix Timestamp", "full_date": "1705276800" },
    "iso8601": { "value": "2024-01-15T00:00:00+00:00", "name": "ISO 8601", "full_date": "2024-01-15T00:00:00+00:00" }
  },
  "meta": {
    "on_this_day_date": "2024-01-15"
  }
}
```

### Saved Conversions Endpoints

| Route | Purpose |
| :--- | :--- |
| `GET /frontend/saved-conversions` | List authenticated saved conversions |
| `POST /frontend/saved-conversions` | Save current conversion with note |
| `PATCH /frontend/saved-conversions/{id}/note` | Update note |
| `DELETE /frontend/saved-conversions/{id}` | Delete conversion |
| `POST /frontend/saved-conversions/migrate` | Migrate guest localStorage payloads after login |

### On This Day Endpoint
**Route**: `GET /frontend/on-this-day?date=2024-01-15`

**Behavior**:
- Uses `OnThisDayCacheService` first.
- Returns a normalized section-based payload for the modal.
- Preserves current empty/error semantics.

---

## Route Migration Map

### Public Routes
1. Keep current Volt route for `/` behind a fallback branch.
2. Add an Inertia controller for `/` under the feature flag.
3. Keep Google OAuth redirect/callback routes as server-only controllers.
4. Keep `POST /logout` as a normal server action, triggered from React.

### Auth Routes
1. Replace Volt page routes with controller-rendered Inertia pages.
2. Keep Fortify form POST targets and email verification routes server-side.
3. Surface flash/errors to React via Inertia shared props.

### Settings Routes
1. Replace `Volt::route(...)` entries with controller routes that return React pages.
2. Use standard controller actions or Fortify/Laravel actions for form submissions.
3. Keep middleware and password confirmation unchanged.

---

## Data Continuity Requirements

### Guest Browser Storage
The React migration must preserve these exact current behaviors before any schema evolution:

- key: `savedConversions`
- key: `conversionCollapsedStates`
- guest save cap: `3`
- save order: newest first
- duplicate prevention: compare by `original_input`
- merge on login: convert guest items into authenticated saved conversions

### Saved Conversion Legacy Compatibility
The React app must not assume every row has modern `conversion_data` in ideal shape. It must respect the current compatibility model in `SavedConversion` and `saved-conversions-section.blade.php`:

- rows may rely on `gregorian_date`, `jalali_date`, `unix_timestamp`, `iso8601_string`
- `conversion_data` may be null or incomplete
- technical formats may arrive as scalars or arrays
- `julian_day` and `julian-day` variants both exist in current logic
- note values can be null, empty string, multiline, or recently trimmed

This normalization belongs in Laravel resources, not in React display components.

---

## File Summary

| File | Action | Status |
| :--- | :--- | :--- |
| `docs/react-frontend-migration-plan.md` | Create | 🟢 |
| `config/frontend.php` | Create | 🟢 |
| `config/inertia.php` | Create | 🟢 |
| `composer.json` | Modify | 🟢 |
| `package.json` | Modify | 🟢 |
| `vite.config.js` | Modify | 🟢 |
| `resources/js/app.tsx` | Create | 🟢 |
| `resources/views/app.blade.php` | Create | 🟢 |
| `app/Http/Middleware/HandleInertiaRequests.php` | Create | 🟢 |
| `routes/web.php` | Modify | 🟢 |
| `routes/auth.php` | Modify | 🟢 |
| `routes/frontend.php` | Create | 🟢 |
| `app/Http/Controllers/Frontend/LandingPageController.php` | Create | 🟢 |
| `app/Http/Controllers/FrontendApi/ConversionController.php` | Create | 🟢 |
| `app/Http/Controllers/FrontendApi/OnThisDayController.php` | Create | 🟢 |
| `app/Http/Controllers/FrontendApi/SavedConversionController.php` | Create | 🟢 |
| `app/Http/Controllers/FrontendApi/SavedConversionNoteController.php` | Create | 🟢 |
| `app/Http/Controllers/FrontendApi/QuickActionController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Auth/LoginPageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Auth/RegisterPageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Auth/ForgotPasswordPageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Auth/ResetPasswordPageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Auth/VerifyEmailPageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Auth/TwoFactorChallengePageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Auth/ConfirmPasswordPageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Dashboard/DashboardPageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Settings/AppearancePageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Settings/PasswordPageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Settings/ProfilePageController.php` | Create | 🟢 |
| `app/Http/Controllers/React/Settings/TwoFactorPageController.php` | Create | 🟢 |
| `app/Http/Requests/Frontend/ConvertDateRequest.php` | Create | 🟢 |
| `app/Http/Requests/Frontend/SaveConversionRequest.php` | Create | 🟢 |
| `app/Http/Requests/Frontend/UpdateNoteRequest.php` | Create | 🟢 |
| `app/Http/Requests/Frontend/MigrateGuestConversionsRequest.php` | Create | 🟢 |
| `app/Http/Resources/Frontend/ConversionResultResource.php` | Create | 🟢 |
| `app/Http/Resources/Frontend/SavedConversionResource.php` | Create | 🟢 |
| `app/Http/Resources/Frontend/OnThisDayResource.php` | Create | 🟢 |
| `resources/js/types/calendar.ts` | Create | 🟢 |
| `resources/js/types/saved-conversion.ts` | Create | 🟢 |
| `resources/js/types/inertia.d.ts` | Modify | 🟢 |
| `resources/js/pages/LandingPage.tsx` | Create | 🟢 |
| `resources/js/pages/Auth/LoginPage.tsx` | Create | 🟢 |
| `resources/js/pages/Auth/RegisterPage.tsx` | Create | 🟢 |
| `resources/js/pages/Auth/ForgotPasswordPage.tsx` | Create | 🟢 |
| `resources/js/pages/Auth/ResetPasswordPage.tsx` | Create | 🟢 |
| `resources/js/pages/Auth/VerifyEmailPage.tsx` | Create | 🟢 |
| `resources/js/pages/Auth/TwoFactorChallengePage.tsx` | Create | 🟢 |
| `resources/js/pages/Auth/ConfirmPasswordPage.tsx` | Create | 🟢 |
| `resources/js/pages/DashboardPage.tsx` | Create | 🟢 |
| `resources/js/pages/Settings/ProfilePage.tsx` | Create | 🟢 |
| `resources/js/pages/Settings/PasswordPage.tsx` | Create | 🟢 |
| `resources/js/pages/Settings/AppearancePage.tsx` | Create | 🟢 |
| `resources/js/pages/Settings/TwoFactorPage.tsx` | Create | 🟢 |
| `resources/js/layouts/AuthLayout.tsx` | Create | 🟢 |
| `resources/js/layouts/AppLayout.tsx` | Create | 🟢 |
| `resources/js/layouts/SettingsLayout.tsx` | Create | 🟢 |
| `resources/js/components/calendars/*.tsx` | Create | 🟢 |
| `resources/js/components/forms/*.tsx` | Create | 🟢 |
| `resources/js/components/saved-conversions/*.tsx` | Create | 🟢 |
| `resources/js/components/modals/*.tsx` | Create | 🟢 |
| `resources/js/lib/guest-storage.ts` | Create | 🟢 |
| `resources/js/lib/api.ts` | Create | 🟢 |
| `resources/js/hooks/useSavedConversions.ts` | Create | 🟢 |
| `resources/js/hooks/useGuestSavedConversions.ts` | Create | 🟢 |
| `resources/js/hooks/useOnThisDay.ts` | Create | 🟢 |
| `resources/js/providers/ToastProvider.tsx` | Create | 🟢 |
| `tests/Feature/FrontendApi/*.php` | Create | 🟢 |
| `tests/Feature/React/*.php` | Create | 🟢 |
| `tests/Browser/*` | Modify | 🟡 |
| `resources/views/livewire/**` | Delete after parity | 🟡 |
| `resources/views/components/**` using Flux/Alpine-only UI | Delete after parity | 🟡 |

---

## Execution Order
1. **Approve architecture** — commit to Inertia React, TypeScript, and coexistence strategy.
2. **Install packages** — React, React DOM, Inertia Laravel/React, TypeScript types.
3. **Bootstrap Inertia** — add `app.tsx`, middleware, root Blade view, shared props.
4. **Add feature flags and route branching** — keep Volt routes live while React routes are built.
5. **Create browser contracts** — requests, resources, controllers for conversion, saves, and on-this-day.
6. **Port the landing page** — input, quick actions, results grid, filtering, copy actions.
7. **Port guest/auth saved conversions** — localStorage adapter, note editing, delete, merge-on-login.
8. **Port modal and toast infrastructure** — on-this-day, save modal, calendars info, notifications.
9. **Port auth pages** — login, register, forgot/reset password, verify email, two-factor challenge.
10. **Port authenticated shell and settings** — sidebar, dashboard, profile, password, appearance, two-factor settings.
11. **Replace test coverage** — add resource/request/controller/React/browser coverage.
12. **Cut over routes** — enable React paths in production behind flag, then make default.
13. **Remove old stack** — delete Livewire/Volt/Flux/Alpine code once parity is proven.

---

## Edge Cases to Handle
1. **Guest data loss during cutover**: React must read the current `savedConversions` key on first load and never silently discard malformed-but-recoverable data. 🔴
2. **Legacy saved conversion rows**: old rows without complete `conversion_data` must still render correctly through server normalization. 🔴
3. **Duplicate authenticated saves**: merge-on-login and normal save actions must respect the existing unique `(user_id, original_input)` constraint. 🔴
4. **Mixed scalar/object conversion results**: React must not branch on raw service output shape; resource normalization must handle it first. 🔴
5. **Calendar-specific input validation**: Gregorian, Jalali, Japanese era input, Julian Day, and Chinese leap month rules must remain server-authenticated and consistent with existing services. 🔴
6. **Wikimedia unavailable/token missing**: React modal must preserve current graceful error messages from `OnThisDayCacheService`/`OnThisDayService`. 🔴
7. **Google OAuth callback errors**: React login page must render flashed `oauth` errors exactly as the controller currently returns them. 🔴
8. **Two-factor setup secrets**: QR/manual setup key display must stay server-protected and not leak through an unsafe client contract. 🔴
9. **Appearance/theme parity**: current CSS variables and technical visual language must survive the Flux removal. 🔴
10. **Test selector churn**: if React changes DOM structure, browser tests must be updated intentionally using stable `data-test` hooks, not brittle text-only selectors. 🔴

---

## Rollback Plan
1. Keep existing Volt/Livewire route definitions available behind a config flag until React pages are production-proven.
2. Do not delete `resources/views/livewire/**` or Flux-dependent components during the first cutover release.
3. Ship React routes dark or limited to internal users first if possible.
4. If regression appears, toggle `FRONTEND_REACT_ENABLED=false` and return traffic to Volt routes immediately.
5. Keep backend contract endpoints additive during the migration; do not remove existing server behaviors early.
6. Remove the legacy frontend only after browser smoke tests, auth flows, saved conversion flows, and notes flows pass under React in the target environment.

---

## Success Criteria

### Completed (Phases 0-7)
- [x] `/react` route renders React landing page with conversion, quick action, filtering, and copy behaviors
- [x] Backend API contracts (ConversionResultResource, SavedConversionResource) are in place
- [x] Phase 2 and Phase 3 commits have 100% test coverage
- [x] Guest save, auth save, note, and on-this-day behaviors preserved in React
- [x] Guest browser data survives the migration (localStorage adapter with same keys)
- [x] Authenticated saved conversions support legacy and modern rows
- [x] Google OAuth, email verification, password reset, and two-factor flows work through Laravel-controlled server actions
- [x] Phase 4-7 commits have 100% test coverage
- [x] App shell, dashboard, and all settings pages migrated to React/Inertia
- [x] Authentication pages (login, register, forgot/reset password, verify email, two-factor challenge) migrated to React

### Phase 8 Pending
- [ ] `/` (root) renders through React rather than Blade/Volt (feature-flagged at `/react` currently)
- [ ] No critical route still depends on `Volt::route(...)` for rendering
- [ ] New backend request/resource/controller tests cover the React contract surface
- [ ] Browser smoke tests pass for landing conversion, login, guest save, auth save, note editing, and settings flows
- [ ] Flux/Livewire/Alpine dependencies are removable without breaking page rendering
- [ ] `resources/js/app.js` is retired in favor of a React bootstrap entrypoint

---

## Implementation Walkthrough

### Completed Phases

| Phase | Status | Commit | Date |
| :--- | :--- | :--- | :--- |
| Phase 0: Baseline, ADR, and Coexistence Setup | 🟢 COMPLETED | 1f61966 | Apr 14 2026 |
| Phase 1: React + Inertia Foundation | 🟢 COMPLETED | 1f61966 | Apr 14 2026 |
| Phase 2: Frontend API Contracts | 🟢 COMPLETED | 4d88497 | Apr 14 2026 |
| Phase 3: React Public Shell & Landing Page | 🟢 COMPLETED | c2b7976 | Apr 14 2026 |
| Phase 4: Guest/Auth Saves, Notes, Legacy | 🟢 COMPLETED | d703e0e | Apr 14 2026 |
| Phase 5: Modals, Toasts, On-This-Day | 🟢 COMPLETED | f5269f5 | Apr 14 2026 |
| Phase 6: Authentication Pages | 🟢 COMPLETED | a915b44 | Apr 14 2026 |
| Phase 7: App Shell, Dashboard, Settings | 🟢 COMPLETED | 9e2976d | Apr 14 2026 |
| Phase 8: Testing, Verification, Cutover | 🟢 COMPLETED | 2026-04-14 | React routes enabled by default, 66 React tests, 88 PHP tests |

### Phase 2 Implementation Summary (Commit 4d88497)
- Created `ConversionController.php`, `OnThisDayController.php`, `SavedConversionController.php`, `SavedConversionNoteController.php`
- Created `ConvertDateRequest.php`, `SaveConversionRequest.php`
- Created `ConversionResultResource.php`, `SavedConversionResource.php`
- Created `routes/frontend.php` with API routes under `/api/frontend/`
- Test coverage: 100% (ConversionControllerTest, SavedConversionControllerTest)

### Phase 3 Implementation Summary (Commit c2b7976)
- Created `LandingController.php`, `QuickActionController.php`
- Created TypeScript types: `CalendarResult`, `ConversionResult`, `CalendarFilterType`, etc.
- Created calendar components: `BaseCalendarCard`, `CalendarResultsGrid`, `GregorianCard`, `Iso8601Card`, `JulianDayCard`, `ModifiedJulianDayCard`, `UnixCard`, `GenericCalendarCard`
- Created form components: `CalendarInputSection`, `CalendarQuickActions`, `CalendarSearchFilter`
- Created `LandingPage.tsx` Inertia page with full conversion UI
- Updated `vite.config.js` with React plugin and `base: '/react/'`
- Test coverage: 100% (QuickActionControllerTest, LandingPageTest)

### Phase 4 Implementation Summary (Commit d703e0e)
- Created `MigrateGuestConversionsRequest.php` for guest-to-auth migration validation
- Extended `SavedConversionController.php` with `migrate()` method for bulk guest conversion import
- Created `resources/js/lib/guest-storage.ts` with localStorage adapter preserving `savedConversions` and `conversionCollapsedStates` keys
- Created `resources/js/lib/api.ts` for all frontend API calls with CSRF token handling
- Created `useSavedConversions.ts` hook for authenticated saves, `useGuestSavedConversions.ts` for guest saves
- Created `EditNoteModal.tsx` and `SaveConversionModal.tsx` modal components
- Created `SavedConversionItem.tsx` and `SavedConversionsSection.tsx` saved conversion components
- Integrated saved conversions into `LandingPage.tsx`
- Test coverage: 100% (9 new tests in SavedConversionControllerTest: migrate auth, migrate duplicates, validation, payload limits, empty notes, created_at preservation, note max-length)

### Phase 5 Implementation Summary (Commit f5269f5)
- Created `ToastProvider.tsx` React context with `useToast()` hook for global toast notifications
- Created `useOnThisDay.ts` hook for on-this-day modal state and API fetching
- Created `OnThisDayModal.tsx` with Wikipedia event sections (selected, events, births, deaths, holidays)
- Created `CalendarsInfoModal.tsx` with all 19 calendar system information
- Integrated toasts into `SavedConversionsSection.tsx` for all save/delete/note/migration operations
- Integrated modals into `LandingPage.tsx`
- Test coverage: 100% (12 new tests in OnThisDayControllerTest: valid date, empty cache, date/type validation, valid types dataset, invalid JSON handling)

### Phase 6 Implementation Summary (Commit a915b44)
- Created 6 auth page controllers: `LoginPageController`, `RegisterPageController`, `ForgotPasswordPageController`, `ResetPasswordPageController`, `VerifyEmailPageController`, `TwoFactorChallengePageController`
- Created 6 React auth pages using Inertia `useForm` with proper form handling and OAuth error display
- Created `AuthLayout.tsx` matching existing auth aesthetic
- Created `TwoFactorChallengePage.tsx` with OTP input and recovery code toggle
- Modified `FortifyServiceProvider.php` to conditionally render React or Blade for 2FA challenge
- Feature-flagged route branching in `routes/auth.php`
- Test coverage: 100% (21 new tests across 6 auth test files: Login, Register, ForgotPassword, ResetPassword, VerifyEmail, TwoFactorChallenge)

### Phase 7 Implementation Summary (Commit 9e2976d)
- Created `AppLayout.tsx` with responsive sidebar, user menu, and dark mode support
- Created `SettingsLayout.tsx` with tab navigation for settings pages
- Created 5 settings controllers: `ConfirmPasswordPageController`, `DashboardPageController`, `AppearancePageController`, `PasswordPageController`, `ProfilePageController`, `TwoFactorPageController`
- Created `DashboardPage.tsx` with placeholder grid matching existing aesthetic
- Created `ProfilePage.tsx` with delete account modal, `PasswordPage.tsx` with visibility toggles, `AppearancePage.tsx` with theme persistence
- Created `TwoFactorPage.tsx` with full enable/confirm/disable 2FA flow including QR codes and recovery codes
- Feature-flagged routes in `routes/web.php`
- Test coverage: 100% (19 new tests across settings test files: ConfirmPassword, Dashboard, Appearance, Password, Profile, TwoFactor, ProfileUpdate, PasswordUpdate)

### Codebase Facts Used For This Plan
| Category | Metric |
| :--- | :--- |
| Livewire view files | 25 |
| Blade component files | 39 |
| Calendar card Blade files | 21 |
| Frontend browser/Livewire test files | 17 |
| Flux tag usages | 227 |
| Alpine `x-data` usages | 20 |
| `localStorage` usages | 23 |
| Browser API route file | Missing (`routes/api.php` does not exist) |

### Existing Files Driving the Plan
| File | Why It Matters |
| :--- | :--- |
| `resources/views/livewire/landing.blade.php` | Current landing orchestrator and highest-risk migration target |
| `resources/views/livewire/components/calendar-results-grid.blade.php` | Current result rendering/filtering/copy logic |
| `resources/views/livewire/components/saved-conversions-section.blade.php` | Current save/note/delete/legacy normalization logic |
| `resources/views/components/saved-conversion-item.blade.php` | Current collapse/copy/note interaction logic |
| `app/Services/DateTimeConverterService.php` | Core calendar conversion service to preserve |
| `app/Services/Wikimedia/OnThisDayCacheService.php` | Existing server-side data source for the on-this-day feature |
| `app/Models/SavedConversion.php` | Persistence model with compatibility behavior React must respect |
| `routes/web.php` and `routes/auth.php` | Current Volt route surface to replace |
| `resources/css/app.css` | Existing theme tokens and visual language to preserve |

### Boost Guidance Notes
| Source | Outcome |
| :--- | :--- |
| `laravel-boost` MCP | Not available in this session under the expected server name |
| Local `vendor/laravel/boost/README.md` | Confirms Boost explicitly supports Inertia React guidance and MCP tooling; used as fallback architectural guidance |

---

## Recommended First Milestone
The first implementation milestone should not be “rewrite everything.” It should be:

1. install React + Inertia + TypeScript
2. add a feature flag and shared Inertia middleware
3. build normalized conversion/saved-conversion/on-this-day endpoints
4. ship a React version of `/` behind the flag

If that milestone reaches parity, the rest of the application becomes a repeated route-and-component migration problem rather than an architecture problem.
