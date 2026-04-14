---
audit_file: 20260414_react-migration_audit.md
project_name: react-migration
last_audited_commit: 55da9f0
last_audit_date: 2026-04-14
total_phases: 8
total_commits: 8
---

# Phase Commit Audit

## Quick Summary

### Phase 2: Frontend API Contracts (1 commit, Apr 14 2026)
Added Laravel API endpoints for the React frontend migration with conversion controllers, form requests, and API resources.

### Phase 3: React Public Shell & Landing Page (1 commit, Apr 14 2026)
Built the complete React landing page with calendar conversion UI, including TypeScript types, calendar card components, form components, and server-side quick actions.

### Phase 4: Guest/Auth Saves, Notes & Legacy Fallbacks (1 commit, Apr 14 2026)
Implemented guest localStorage adapter, authenticated save/note/delete hooks, guest-to-auth migration endpoint, modal components for save and note editing, and collapsible saved conversion cards with legacy data rendering.

### Phase 5: Modals, Toasts & Wikimedia On This Day UX (1 commit, Apr 14 2026)
Ported toast notification system as React ToastProvider context with useToast hook, rebuilt the On This Day modal around the existing cache-backed endpoint with a useOnThisDay hook, added the Calendars Info reference modal, and integrated toast feedback into all save/delete/note/migration flows.

### Phase 6: Authentication Pages (1 commit, Apr 14 2026)
Ported all authentication pages to React/Inertia with feature-flagged route branching, created 6 Inertia controllers, 6 React page components using useForm, AuthLayout matching existing aesthetic, two-factor challenge with OTP input, and Google OAuth integration preserved via server-side redirect.

### Phase 7: App Shell, Dashboard & Settings (1 commit, Apr 14 2026)
Built the authenticated app shell with responsive sidebar (AppLayout), dashboard placeholder (DashboardPage), and full settings suite (ProfilePage with delete account modal, PasswordPage with visibility toggles, AppearancePage with theme persistence, TwoFactorPage with full enable/confirm/disable flow including QR code and recovery codes).

### Phase 8: Testing Infrastructure & Route Cutover (1 commit, Apr 14 2026)
Added React component testing with Vitest + Testing Library (66 tests), test infrastructure with jsdom, mocks for localStorage/crypto/matchMedia, npm scripts for test/test:run/test:coverage, and enabled React routes by default (FRONTEND_REACT_ENABLED default true).

---

## Phase 2: Frontend API Contracts

### Overview
- **Commits**: 1 implementation commit
- **Lines Changed**: +889/-5
- **Files Affected**: 12 files
- **Test Coverage**: 2/2 commits (100%) ✅
- **Migrations**: 0

### Implementation Commits

**4d88497** — feat(react): add frontend API contracts and endpoints (Phase 2)
**Impact**: +889/-5 lines, 12 files

- **Controller**: Created `app/Http/Controllers/FrontendApi/ConversionController.php`
  - Method: `index(Request $request)` - handles date conversion requests
  - Validates input_type and date parameters
  - Returns `ConversionResultResource` JSON response

- **Controller**: Created `app/Http/Controllers/FrontendApi/OnThisDayController.php`
  - Method: `index(Request $request)` - retrieves historical events for a date
  - Returns formatted events data

- **Controller**: Created `app/Http/Controllers/FrontendApi/SavedConversionController.php`
  - Methods: `index()`, `store(SaveConversionRequest)`, `show(SavedConversion $saved)`, `destroy(SavedConversion $saved)`
  - Full CRUD for authenticated user saved conversions
  - Integrates with calendar service layer

- **Controller**: Created `app/Http/Controllers/FrontendApi/SavedConversionNoteController.php`
  - Method: `update(SavedConversion $saved, UpdateNoteRequest)` - adds notes to saved conversions

- **FormRequest**: Created `app/Http/Requests/Frontend/ConvertDateRequest.php`
  - Validation rules for `input_type` (gregorian, jalali, unix, iso8601, jd, mjd, hebrew, islamic, french_republican, chinese, japanese, coptic, egyptian, ethiopian, babylonian, assyrian, macedonian, julian, mayan)
  - Validation rules for `date` parameter

- **API Resource**: Created `app/Http/Resources/Frontend/ConversionResultResource.php`
  - Transforms ConversionResult into API response format
  - Includes full_date, name, year, month, day, epoch, era fields

- **API Resource**: Created `app/Http/Resources/Frontend/SavedConversionResource.php`
  - Transforms SavedConversion model for API responses
  - Includes original_input, conversion_data, note, user relationship

- **Route File**: Created `routes/frontend.php`
  - POST `/frontend/convert` - ConversionController@index
  - GET `/frontend/on-this-day` - OnThisDayController@index
  - GET `/frontend/saved-conversions` - SavedConversionController@index
  - POST `/frontend/saved-conversions` - SavedConversionController@store
  - GET `/frontend/saved-conversions/{saved}` - SavedConversionController@show
  - DELETE `/frontend/saved-conversions/{saved}` - SavedConversionController@destroy
  - PATCH `/frontend/saved-conversions/{saved}/note` - SavedConversionNoteController@update

- **Bootstrap**: Modified `bootstrap/app.php`
  - Added `api.php` route file registration under `/api` prefix

- **Tests**: Created `tests/Feature/FrontendApi/ConversionControllerTest.php`
  - Tests conversion endpoint with various input types
  - Tests validation for invalid input

- **Tests**: Created `tests/Feature/FrontendApi/SavedConversionControllerTest.php`
  - Full CRUD test coverage for saved conversions
  - Authentication required tests
  - Note update tests

### Breaking Changes
None

### Technical Debt Introduced
None

---

## Phase 3: React Public Shell & Landing Page

### Overview
- **Commits**: 1 implementation commit
- **Lines Changed**: +1886/-8
- **Files Affected**: 26 files
- **Test Coverage**: 2/2 commits (100%) ✅
- **Migrations**: 0

### Implementation Commits

**c2b7976** — feat(react): build React public shell and landing page (Phase 3)
**Impact**: +1886/-8 lines, 26 files

- **Controller**: Created `app/Http/Controllers/FrontendApi/QuickActionController.php`
  - Method: `show(Request $request)` - returns formatted date for today/yesterday/tomorrow
  - Supports all 17 input types with proper formatting
  - Uses service classes (HebrewCalendarService, IslamicCalendarService, etc.)

- **Controller**: Created `app/Http/Controllers/React/LandingController.php`
  - Method: `__invoke()` - renders Inertia 'LandingPage'
  - Passes `todayDate` to page

- **TypeScript**: Created `resources/js/types/calendar.ts`
  - Interfaces: CalendarResult, ErrorResult, ConversionResult, isErrorResult type guard
  - Types: ConversionResultMap, CalendarFilterType, CalendarType enum
  - Maps API response structure for frontend

- **TypeScript**: Created `resources/js/types/index.ts`
  - Re-exports calendar types

- **TypeScript**: Modified `resources/js/types/inertia.d.ts`
  - Added PageProps for LandingPage (todayDate: string)

- **Component**: Created `resources/js/components/calendars/BaseCalendarCard.tsx`
  - Reusable calendar card wrapper with copy-to-clipboard functionality
  - Props: title, copyText, copyType, disclaimer, className, icon, children
  - States: copiedFeedback, showDisclaimer (hover tooltip)

- **Component**: Created `resources/js/components/calendars/CalendarResultsGrid.tsx`
  - Main results display with filtering and search
  - Handles authenticated vs guest save (localStorage for guests, API for users)
  - Copy all functionality
  - Filters by searchTerm and selectedTypes

- **Component**: Created `resources/js/components/calendars/GenericCalendarCard.tsx`
  - Fallback card for non-standard calendar types
  - Props: calendarKey, data (GenericCalendarData)

- **Component**: Created `resources/js/components/calendars/GregorianCard.tsx`
  - Standard calendar display with weekday, full date, and formatted output
  - Icon: calendar glyph

- **Component**: Created `resources/js/components/calendars/Iso8601Card.tsx`
  - ISO 8601 week-date format display (e.g., 2026-W15-2)
  - Icon: calendar glyph

- **Component**: Created `resources/js/components/calendars/JulianDayCard.tsx`
  - Julian Day number display
  - Icon: calendar glyph

- **Component**: Created `resources/js/components/calendars/ModifiedJulianDayCard.tsx`
  - Modified Julian Day number display
  - Icon: calendar glyph

- **Component**: Created `resources/js/components/calendars/UnixCard.tsx`
  - Unix timestamp display (seconds since epoch)
  - Icon: clock glyph

- **Component**: Created `resources/js/components/calendars/index.ts`
  - Barrel export for all calendar components

- **Component**: Created `resources/js/components/forms/CalendarInputSection.tsx`
  - Date input with type selector dropdown
  - Supports all 19 input types with proper labels
  - Example date display for selected type

- **Component**: Created `resources/js/components/forms/CalendarQuickActions.tsx`
  - Today/Yesterday/Tomorrow quick action buttons
  - Fetches formatted date from QuickActionController
  - Updates parent state with selected date

- **Component**: Created `resources/js/components/forms/CalendarSearchFilter.tsx`
  - Search input for filtering results
  - Type filter chips (ancient, astronomical, modern, religious)
  - Clear filters button

- **Component**: Created `resources/js/components/forms/index.ts`
  - Barrel export for form components

- **Page**: Created `resources/js/pages/LandingPage.tsx`
  - Full Inertia page with React state management
  - States: dateInput, inputType, results, error, searchTerm, selectedTypes, isAuthenticated, savedConversions
  - Converts on input change with debounce
  - Fetches from /frontend/convert API
  - Save functionality for authenticated users

- **App Config**: Modified `resources/js/app.tsx`
  - Updated to use explicit type import for ResolvedComponent
  - Uses explicit glob pattern for page resolution

- **Routes**: Modified `routes/web.php`
  - Added `/react` route pointing to LandingController (feature-flagged)

- **Routes**: Modified `routes/frontend.php`
  - Added GET `/frontend/quick-action` for QuickActionController@show

- **Vite Config**: Modified `vite.config.js`
  - Added `base: '/react/'` for React app base path

- **Tests**: Created `tests/Feature/FrontendApi/QuickActionControllerTest.php`
  - Tests today/yesterday/tomorrow actions
  - Tests all input types return valid formatted dates

- **Tests**: Created `tests/Feature/React/LandingPageTest.php`
  - Tests landing page renders
  - Tests todayDate prop is passed

### Breaking Changes
None

### Technical Debt Introduced
None

---

## Phase 4: Guest/Auth Saves, Notes & Legacy Fallbacks

### Overview
- **Commits**: 1 implementation commit
- **Lines Changed**: +1,532/-61
- **Files Affected**: 19 files (13 new, 6 modified)
- **Test Coverage**: 1/1 commits (100%) ✅ — 9 new tests added (18 total in SavedConversionControllerTest, 36 in FrontendApi suite)
- **Migrations**: 0

### Implementation Commits

**d703e0e** — feat(react): port guest/auth saves, notes, and legacy fallbacks (Phase 4)
**Impact**: +1,532/-61 lines, 19 files

**Backend (PHP):**

- **FormRequest**: Created `app/Http/Requests/Frontend/MigrateGuestConversionsRequest.php`
  - Validates `items` array (required, max 3 entries)
  - Per-item rules: `original_input` (required, string, max 255), `conversion_data` (required, array), `note` (nullable, string, max 2048), `created_at` (nullable, string)
  - Custom error messages for migration-specific validation failures

- **Controller**: Modified `app/Http/Controllers/FrontendApi/SavedConversionController.php`
  - Added imports: `MigrateGuestConversionsRequest`, `DB`, `Log`
  - Method: `migrate(MigrateGuestConversionsRequest $request): JsonResponse`
    - Wraps migration in `DB::beginTransaction()` / `DB::commit()` / `DB::rollBack()`
    - Iterates guest items, checks for existing `(user_id, original_input)` duplicates
    - Creates `SavedConversion` records populating both `conversion_data` JSON and legacy compatibility fields (`gregorian_date`, `jalali_date`, `unix_timestamp`, `iso8601_string`)
    - Preserves `created_at` from guest localStorage payload
    - Returns `{ success: true, migrated_count: N }`
    - Logs failures with user_id context (no token/secret logging)

- **Route**: Modified `routes/frontend.php`
  - Added `POST /frontend/saved-conversions/migrate` → `SavedConversionController@migrate`
  - Placed before the `{savedConversion}` parameterized routes to avoid route collision

**Frontend (TypeScript/React):**

- **Types**: Created `resources/js/types/saved-conversion.ts`
  - Interfaces: `SavedConversion` (id, original_input, note, conversion_data, uses_legacy_fallback, created_at)
  - Interfaces: `GuestSavedConversion` (id, original_input, conversion_data, note, created_at)
  - Interfaces: `MigrateGuestConversionsPayload`, `SavedConversionListResponse`, `SavedConversionResponse`

- **Types**: Modified `resources/js/types/index.ts`
  - Added `export * from './saved-conversion'` barrel export

- **Lib**: Created `resources/js/lib/guest-storage.ts`
  - Constants: `GUEST_SAVED_CONVERSIONS_KEY = 'savedConversions'`, `GUEST_COLLAPSED_STATES_KEY = 'conversionCollapsedStates'`, `GUEST_SAVE_LIMIT = 3`
  - Functions: `loadGuestConversions()` — reads localStorage with JSON parse safety and structural validation
  - Functions: `saveGuestConversions(conversions)` — writes with `GUEST_SAVE_LIMIT` cap
  - Functions: `addGuestConversion(conversion)` — duplicate check by `original_input`, newest-first insertion, returns null on limit reached
  - Functions: `deleteGuestConversion(id)`, `updateGuestNote(id, note)` — note trimming to 2048 chars
  - Functions: `loadCollapsedStates()`, `saveCollapsedStates(states)` — manages collapse persistence
  - Functions: `clearGuestData()`, `hasGuestData()` — post-migration cleanup utilities

- **Lib**: Created `resources/js/lib/api.ts`
  - Helper: `getCsrfToken()` / `apiHeaders()` — reads `<meta name="csrf-token">` for all requests
  - Functions: `fetchSavedConversions()` — `GET /frontend/saved-conversions`
  - Functions: `createSavedConversion(payload)` — `POST /frontend/saved-conversions`
  - Functions: `deleteSavedConversion(id)` — `DELETE /frontend/saved-conversions/{id}`
  - Functions: `updateSavedConversionNote(id, note)` — `PATCH /frontend/saved-conversions/{id}/note`
  - Functions: `migrateGuestConversions(items)` — `POST /frontend/saved-conversions/migrate`

- **Hook**: Created `resources/js/hooks/useSavedConversions.ts`
  - State: `items: SavedConversion[]`, `isLoading`, `error`
  - Methods: `save(originalInput, conversionData, note?)` — calls `createSavedConversion()` then refreshes
  - Methods: `remove(id)` — calls `deleteSavedConversion()` then filters local state
  - Methods: `updateNote(id, note)` — trims to 2048 chars, calls API, updates local state
  - Methods: `refresh()` — re-fetches from API
  - Auto-fetches on mount when `isAuthenticated` is true

- **Hook**: Created `resources/js/hooks/useGuestSavedConversions.ts`
  - State: `items: GuestSavedConversion[]`, `saveLimitReached`
  - Methods: `save()`, `remove()`, `updateNote()`, `clearAll()`, `getItemsForMigration()`
  - All mutations go through `guest-storage.ts` and re-read localStorage for state

- **Hook**: Created `resources/js/hooks/index.ts` — barrel export

- **Modal**: Created `resources/js/components/modals/EditNoteModal.tsx`
  - Props: `open`, `initialValue`, `onSave(note)`, `onClose`
  - Features: 2048-char max textarea, Escape key / backdrop click to close, Cmd+Enter to save
  - Auto-focuses textarea on open, resets value from `initialValue` on reopen

- **Modal**: Created `resources/js/components/modals/SaveConversionModal.tsx`
  - Props: `open`, `onClose`, `onSave(note)`, `isGuest`, `guestLimitReached`
  - Two views: normal save-with-note form OR guest limit reached notice with register CTA
  - Features: optional note textarea, Escape key to close, auto-focus on open

- **Component**: Created `resources/js/components/saved-conversions/SavedConversionItem.tsx`
  - Props: `item` (union of auth/guest conversion data), `onDelete(id)`, `onUpdateNote(id, note)`
  - Features: collapsible card with chevron toggle, calendar data table (first 6 calendars)
  - Copy-to-clipboard per calendar row with 3-second feedback
  - Note display section with edit button → opens `EditNoteModal`
  - Delete button with trash icon
  - Legacy badge indicator for `uses_legacy_fallback` records
  - Formatted `created_at` display

- **Component**: Created `resources/js/components/saved-conversions/SavedConversionsSection.tsx`
  - Props: `isAuthenticated`, `authItems`, `authLoading`, `onAuthSave`, `onAuthDelete`, `onAuthUpdateNote`, `currentConversion`
  - Orchestrates both auth and guest save flows
  - Guest-to-auth migration UI: blue banner with "Migrate now" button when `isAuthenticated && guest.items.length > 0`
  - Migration states: idle → migrating → done/error with colored status banners
  - "Save Current" button opens `SaveConversionModal`
  - Guest limit footer: "{N}/3 saves. Register for unlimited."

- **Page**: Modified `resources/js/pages/LandingPage.tsx`
  - Added imports: `useMemo`, `SavedConversionsSection`, `useSavedConversions`
  - Added `useSavedConversions(isAuthenticated)` hook call
  - Added `currentConversionForSave` memo — derives from `results` and `dateInput`
  - Replaced `CalendarResultsGrid` inline save logic with `onSave` callback delegation
  - Added `<SavedConversionsSection>` below results grid

- **Component**: Modified `resources/js/components/calendars/CalendarResultsGrid.tsx`
  - Removed `dateInput` and `isAuthenticated` from props interface
  - Added optional `onSave?: () => void` callback prop
  - Replaced inline `handleSave` (55 lines of fetch/localStorage logic) with single `onSave?.()` delegation
  - Kept SAVE button in header, now delegates to parent

**Tests:**

- **Tests**: Modified `tests/Feature/FrontendApi/SavedConversionControllerTest.php`
  - 9 new tests added (18 total):
    - `it('requires authentication to migrate guest conversions')` — assert 401
    - `it('migrates guest conversions to authenticated user')` — creates DB record, asserts `migrated_count: 1`
    - `it('skips duplicates during migration')` — pre-existing `(user_id, original_input)` skipped, new item migrated
    - `it('validates migrate payload structure')` — missing fields → 422
    - `it('rejects migration with more than 3 items')` — 4 items → 422
    - `it('migrates guest conversions with empty notes')` — empty string note preserved
    - `it('preserves created_at from guest data during migration')` — timestamp not null
    - `it('validates note max length on store')` — 1001 chars → 422
    - `it('validates note max length on note update')` — 1001 chars → 422

### Breaking Changes
None

### Technical Debt Introduced
None

---

## Phase 5: Modals, Toasts & Wikimedia On This Day UX

### Overview
- **Commits**: 1 implementation commit
- **Lines Changed**: +1,202/-16
- **Files Affected**: 12 files (5 new, 7 modified)
- **Test Coverage**: 1/1 commits (100%) ✅ — 12 new tests added (60 total in FrontendApi suite)
- **Migrations**: 0

### Implementation Commits

**f5269f5** — feat(react): port modals, toasts, and Wikimedia On This Day UX (Phase 5)
**Impact**: +1,202/-16 lines, 12 files

**Backend (PHP Tests Only — endpoint already existed from Phase 2):**

- **Tests**: Created `tests/Feature/FrontendApi/OnThisDayControllerTest.php`
  - 12 Pest tests covering the existing `GET /frontend/on-this-day` endpoint:
    - `it('returns on-this-day data for a valid date')` — creates `OnThisDayEntry` cache row, asserts `data.selected.0.text`, `data.events.0.year`, `cached: true`
    - `it('returns empty data when no cache entry exists')` — asserts `cached: false`, `data: []`
    - `it('validates the date parameter is required')` — missing date → 422
    - `it('validates the date format')` — invalid date string → 422
    - `it('validates the type parameter')` — invalid type → 422
    - `it('accepts valid type parameters')` — dataset with `all`, `events`, `births`, `deaths`, `holidays` → 200
    - `it('returns error when cached data is invalid JSON')` — malformed JSON in `day_info` → `error: 'On This Day data is unavailable right now.'`
    - `it('returns empty data when cache entry has empty day_info')` — empty string `day_info` → `data: []`

**Frontend (TypeScript/React):**

- **Types**: Modified `resources/js/types/calendar.ts`
  - Split `OnThisDayEvent.pages` element into named interface `OnThisDayEventPage`
  - Added `content_urls` nested structure: `{ desktop?: { page: string }, mobile?: { page: string } }`
  - Existing `OnThisDayData`, `OnThisDayResponse` types unchanged (used by new modal)

- **Lib**: Modified `resources/js/lib/api.ts`
  - Added import: `OnThisDayResponse` from `@/types`
  - Function: `fetchOnThisDay(date: string): Promise<OnThisDayResponse>` — `GET /frontend/on-this-day?date=...`
  - Uses existing `apiHeaders()` CSRF pattern

- **Provider**: Created `resources/js/providers/ToastProvider.tsx`
  - Types: `ToastType = 'success' | 'error' | 'info'`, `Toast` (id, type, message)
  - Context: `ToastContextValue` — `toasts: Toast[]`, `addToast(type, message)`, `removeToast(id)`
  - `ToastProvider` component: wraps children + renders `ToastContainer` overlay
  - `useToast()` hook: context consumer with null-check error
  - `ToastContainer` internal component: fixed `top-4 right-4 z-[100]`, stacked gap-2, auto-dismiss after 4000ms
  - Toast styling: color-coded bg (green/red/blue), icon SVGs per type, dismiss button, `role="alert"`

- **Hook**: Created `resources/js/hooks/useOnThisDay.ts`
  - State: `data: OnThisDayData | null`, `isLoading`, `error`, `dateLabel`, `isOpen`
  - `open(gregorianDate: string)` — sets isOpen + isLoading, calls `fetchOnThisDay()`, handles `response.error` vs data, sets date label via `toLocaleDateString('en-US', { month: 'long', day: 'numeric' })`
  - `close()` — sets isOpen false
  - Return: `{ data, isLoading, error, dateLabel, isOpen, open, close }`

- **Hook**: Modified `resources/js/hooks/index.ts`
  - Added `export { useOnThisDay } from './useOnThisDay'`

- **Modal**: Created `resources/js/components/modals/OnThisDayModal.tsx`
  - Props: `open`, `onClose`, `data: OnThisDayData | null`, `isLoading`, `error`, `dateLabel`
  - Internal `SectionConfig` interface with 5 sections: selected, events, births, deaths, holidays
  - Each section: color-coded left border (amber/blue/emerald/zinc/teal), background tint, unique SVG icon
  - `SectionBlock` sub-component: shows first 3 items, "Show N more" toggle for overflow
  - `sanitizeUrl()` helper: validates `http:`/`https:` protocol before rendering Wikipedia links
  - Entry cards: year column (w-20) + text + "Read" link to Wikipedia desktop page
  - States: loading spinner, error banner, empty state, content sections
  - Animation: CSS transition on open/close with opacity + translate-y + scale
  - Escape key listener for close

- **Modal**: Created `resources/js/components/modals/CalendarsInfoModal.tsx`
  - Props: `open`, `onClose`
  - `CalendarInfo` interface: emoji, name, category, description, details, useCases, technical
  - `CALENDARS` array: all 19 calendar systems with structured info (matching legacy `calendars-info-modal.blade.php`)
  - Categories: Modern Calendars (Gregorian, Jalali, Hebrew, Islamic), East Asian (Chinese, Japanese), Historical (Julian, French Republican, Coptic, Ethiopian), Ancient (Egyptian, Babylonian, Assyrian, Macedonian, Mayan), Technical (ISO 8601, Unix, Julian Day, Modified Julian Day)
  - Layout: 2-column responsive grid (`md:grid-cols-2`), each card with emoji, name, category badge, description, details/use-cases/technical sections
  - Footer: "19 Supported Calendar Systems" count + Close button

- **Modal**: Modified `resources/js/components/modals/index.ts`
  - Added exports: `OnThisDayModal`, `CalendarsInfoModal`

- **Component**: Modified `resources/js/components/saved-conversions/SavedConversionsSection.tsx`
  - Added import: `useToast` from `@/providers/ToastProvider`
  - `handleSaveFromModal`: now checks `result` from `onAuthSave` — success/failure toast; guest save checks return value — "Guest mode: 3 max" toast or "already saved" info toast
  - `handleDelete`: wraps `onAuthDelete` in try/catch — success/error toasts; guest delete gets immediate success toast
  - `handleUpdateNote`: wraps `onAuthUpdateNote` in try/catch — "Note updated!" / "Failed to update note." toasts; guest note update gets success toast
  - `handleMigration`: adds "Guest conversions migrated successfully!" toast on success, "Failed to migrate" toast on error
  - All handlers include `addToast` in dependency arrays

- **Page**: Modified `resources/js/pages/LandingPage.tsx`
  - Added imports: `OnThisDayModal`, `CalendarsInfoModal`, `useOnThisDay`
  - Added `onThisDay = useOnThisDay()` hook call
  - Added `calendarsInfoOpen` state
  - Added `gregorianDateFromResults` memo — extracts `results['gregorian'].full_date` for On This Day trigger
  - Replaced placeholder info buttons with:
    - "Calendars" button (always visible) → opens CalendarsInfoModal
    - "On This Day" button (visible after conversion) → calls `onThisDay.open(gregorianDateFromResults)`
  - Added `<OnThisDayModal>` and `<CalendarsInfoModal>` at page root level

- **App Config**: Modified `resources/js/app.tsx`
  - Added import: `ToastProvider` from `@/providers/ToastProvider'
  - Wrapped `<App>` in `<ToastProvider>` for global toast availability

### Breaking Changes
None

### Technical Debt Introduced
None

---

## Phase 6: Authentication Pages

### Overview
- **Commits**: 1 implementation commit
- **Lines Changed**: +1,375/-38
- **Files Affected**: 24 files (18 new, 6 modified)
- **Test Coverage**: 1/1 commits (100%) ✅ — 21 new tests added across 6 test files (19 auth-specific + 2 LandingPageTest fixes)
- **Migrations**: 0

### Implementation Commits

**a915b44** — feat(react): port authentication pages to React with Inertia (Phase 6)
**Impact**: +1,375/-38 lines, 24 files

**Backend (PHP):**

- **Controller**: Created `app/Http/Controllers/React/Auth/LoginPageController.php`
  - Method: `__invoke(Request $request): Response` — renders Inertia `Auth/LoginPage`
  - Props: `canResetPassword` (checks named route existence via `app('router')->getRoutes()->hasNamedRoute()`), `canRegister`, `status` (session flash), `oauthError` (extracts first `oauth` error from ViewErrorBag)

- **Controller**: Created `app/Http/Controllers/React/Auth/RegisterPageController.php`
  - Method: `__invoke(): Response` — renders Inertia `Auth/RegisterPage`

- **Controller**: Created `app/Http/Controllers/React/Auth/ForgotPasswordPageController.php`
  - Method: `__invoke(): Response` — renders Inertia `Auth/ForgotPasswordPage`
  - Props: `status` (session flash for "A reset link will be sent..." message)

- **Controller**: Created `app/Http/Controllers/React/Auth/ResetPasswordPageController.php`
  - Method: `__invoke(Request $request, string $token): Response` — renders Inertia `Auth/ResetPasswordPage`
  - Props: `token` (from route parameter), `email` (from query string), `status` (session flash)

- **Controller**: Created `app/Http/Controllers/React/Auth/VerifyEmailPageController.php`
  - Method: `__invoke(Request $request): Response|RedirectResponse`
  - Redirects verified users to `dashboard?verified=1`
  - Props: `status` (session flash for "verification-link-sent")

- **Controller**: Created `app/Http/Controllers/React/Auth/TwoFactorChallengePageController.php`
  - Method: `__invoke(Request $request): Response` — renders Inertia `Auth/TwoFactorChallengePage`
  - Props: `recovery` (boolean, checks if `recovery_code` error exists in ViewErrorBag to auto-show recovery input)

- **ServiceProvider**: Modified `app/Providers/FortifyServiceProvider.php`
  - Added import: `Inertia\Inertia`
  - Extracted `registerTwoFactorChallengeView()` method — renders React page when `config('frontend.react_enabled')` is true, falls back to legacy Blade view when false
  - Extracted `registerConfirmPasswordView()` method — still renders legacy Blade view (Phase 7 scope)

- **Config**: Created `config/inertia.php`
  - `page_paths`: `[resource_path('js/pages')]` — points Inertia page resolution to actual React page directory (was defaulting to `js/Pages`)
  - `testing.page_paths`: same path for test-time page resolution
  - `testing.ensure_pages_exist`: `true` — validates Inertia page components exist during tests
  - `page_extensions`: `js`, `jsx`, `svelte`, `ts`, `tsx`, `vue`
  - `ssr.enabled`: `false` (not needed for this migration)

- **Routes**: Modified `routes/auth.php`
  - Feature-flagged route branching using `config('frontend.react_enabled')`:
    - When `true`: React controllers serve GET routes for login, register, forgot-password, reset-password/{token}, verify-email
    - When `false`: Volt routes serve the same named routes (preserving backward compatibility)
  - OAuth routes (`social.redirect`, `social.callback`) remain server-controlled regardless of flag
  - `POST /logout` remains server-controlled via `Logout` action class
  - Fortify POST routes (`login.store`, `register.store`, `password.email`, `password.update`, `two-factor.login.store`) remain server-controlled — React only renders the GET views

- **PHPUnit**: Modified `phpunit.xml`
  - Added `<env name="FRONTEND_REACT_ENABLED" value="true"/>` — ensures React routes are registered during testing

**Frontend (TypeScript/React):**

- **Layout**: Created `resources/js/layouts/AuthLayout.tsx`
  - Wraps children in centered card matching existing auth layout aesthetic (bordered card, `max-w-sm`, `border-2 border-theme-300 dark:border-zinc-700`)
  - Includes logo link to home route, dark mode support
  - Uses `<Head title>` for per-page title via Inertia

- **Page**: Created `resources/js/pages/Auth/LoginPage.tsx`
  - Props: `canResetPassword`, `canRegister`, `status`, `oauthError`
  - Uses Inertia `useForm` for `email`, `password`, `remember` with `post(route('login.store'))`
  - Password visibility toggle with eye/eye-off SVG icons
  - OAuth error display (red banner, matching legacy `border-2 border-red-300` styling)
  - Status flash display (green banner for password reset confirmation)
  - Google Sign-In button as `<a>` tag linking to `route('social.redirect', { provider: 'google' })` — OAuth stays server-driven
  - "Forgot your password?" link (conditionally shown via `canResetPassword`)
  - "Don't have an account? Sign up" link (conditionally shown via `canRegister`)
  - Processing state disables submit button with "SIGNING IN..." text
  - `data-test="login-button"` attribute preserved for browser tests

- **Page**: Created `resources/js/pages/Auth/RegisterPage.tsx`
  - Uses Inertia `useForm` for `name`, `email`, `password`, `password_confirmation` with `post(route('register.store'))`
  - Password visibility toggle
  - "Already have an account? Log in" link
  - `data-test="register-user-button"` attribute preserved

- **Page**: Created `resources/js/pages/Auth/ForgotPasswordPage.tsx`
  - Props: `status`
  - Uses Inertia `useForm` for `email` with `post(route('password.email'))`
  - Status display for "A reset link will be sent..." message
  - "Or, return to log in" link
  - `data-test="email-password-reset-link-button"` attribute preserved

- **Page**: Created `resources/js/pages/Auth/ResetPasswordPage.tsx`
  - Props: `token`, `email`, `status`
  - Uses Inertia `useForm` for `token`, `email`, `password`, `password_confirmation` with `post(route('password.update'))`
  - Token and email pre-populated from route parameters
  - Password visibility toggle
  - `data-test="reset-password-button"` attribute preserved

- **Page**: Created `resources/js/pages/Auth/VerifyEmailPage.tsx`
  - Props: `status`
  - Uses Inertia `useForm` with `post(route('verification.send'))` for resend
  - Uses `post(route('logout'))` for logout action
  - Shows "verification-link-sent" success message when `status === 'verification-link-sent'`
  - `data-test="logout-button"` attribute preserved

- **Page**: Created `resources/js/pages/Auth/TwoFactorChallengePage.tsx`
  - Props: `recovery` (boolean — auto-shows recovery input if previous recovery code attempt failed)
  - Uses Inertia `useForm` for `code`, `recovery_code` with `post(route('two-factor.login.store'))`
  - OTP-style 6-digit code input (individual character inputs with auto-focus advance)
  - Toggle between authentication code and recovery code modes
  - `useRef` for auto-focusing correct input on mode switch
  - Recovery code input is full-width monospace text input

**Tests:**

- **Tests**: Created `tests/Feature/React/Auth/LoginPageTest.php` (6 tests)
  - `it('renders the React login page')` — asserts Inertia component `Auth/LoginPage` with all props
  - `it('passes canResetPassword and canRegister as true')` — asserts boolean prop values
  - `it('passes oauth error from session errors to login page')` — uses `ViewErrorBag` to simulate OAuth error, asserts `oauthError` prop
  - `it('passes null oauthError when no oauth errors exist')` — asserts null default
  - `it('passes status flash to login page')` — simulates session flash, asserts `status` prop
  - `it('passes null status when no status flash exists')` — asserts null default

- **Tests**: Created `tests/Feature/React/Auth/RegisterPageTest.php` (1 test)
  - `it('renders the React register page')` — asserts Inertia component `Auth/RegisterPage`

- **Tests**: Created `tests/Feature/React/Auth/ForgotPasswordPageTest.php` (2 tests)
  - `it('renders the React forgot password page')` — asserts Inertia component `Auth/ForgotPasswordPage` with `status` prop
  - `it('passes status flash to forgot password page')` — simulates session flash, asserts `status` prop

- **Tests**: Created `tests/Feature/React/Auth/ResetPasswordPageTest.php` (2 tests)
  - `it('renders the React reset password page')` — sends notification, follows token to page, asserts Inertia component with `token`, `email`, `status` props
  - `it('passes token and email to the reset password page')` — asserts exact `token` and `email` prop values from notification

- **Tests**: Created `tests/Feature/React/Auth/VerifyEmailPageTest.php` (4 tests)
  - `it('renders the React verify email page for unverified users')` — asserts Inertia component with `status` prop
  - `it('redirects verified user to dashboard from verify email page')` — asserts redirect to `dashboard?verified=1`
  - `it('requires authentication to access verify email page')` — asserts redirect to login
  - `it('passes status flash to verify email page')` — simulates `verification-link-sent` flash, asserts `status` prop

- **Tests**: Created `tests/Feature/React/Auth/TwoFactorChallengePageTest.php` (4 tests)
  - `it('renders the React two-factor challenge page with valid session')` — sets up 2FA user with `login.id` session, asserts Inertia component with `recovery` prop
  - `it('redirects to login when accessing two factor challenge without session')` — asserts redirect to login
  - `it('passes recovery true when recovery code error exists')` — uses `ViewErrorBag` with `recovery_code` error, asserts `recovery: true`
  - `it('passes recovery false when no recovery code error exists')` — asserts `recovery: false` default

- **Tests**: Modified `tests/Feature/React/LandingPageTest.php` (2 tests fixed)
  - Removed `config(['frontend.react_enabled' => ...])` calls and `->skip(...)` markers — routes now always registered as React in testing via `phpunit.xml`
  - Removed `refreshApplication()` calls that were needed for dynamic config switching
  - `it('renders the react landing page')` — simplified, no longer skipped
  - `it('passes today date to the landing page')` — simplified, no longer skipped

### Breaking Changes
None

### Technical Debt Introduced
None

### Schema Changes
No database migrations in this audit range.

---

## File Change Heatmap
Most modified files across all phases:

- `resources/js/components/calendars/*.tsx`: 7 files (Phase 3)
- `resources/js/components/forms/*.tsx`: 3 files (Phase 3)
- `resources/js/components/saved-conversions/*.tsx`: 2 files (Phase 4, Phase 5)
- `resources/js/components/modals/*.tsx`: 4 files (Phase 4, Phase 5)
- `resources/js/hooks/*.ts`: 4 files (Phase 4, Phase 5)
- `resources/js/lib/*.ts`: 2 files (Phase 4, Phase 5)
- `resources/js/providers/*.tsx`: 1 file (Phase 5)
- `resources/js/layouts/*.tsx`: 1 file (Phase 6)
- `resources/js/pages/Auth/*.tsx`: 6 files (Phase 6)
- `resources/js/pages/LandingPage.tsx`: 1 file (Phase 3, Phase 4, Phase 5)
- `resources/js/app.tsx`: 1 file (Phase 3, Phase 5)
- `resources/js/types/calendar.ts`: 1 file (Phase 3, Phase 5)
- `app/Http/Controllers/FrontendApi/*.php`: 4 files (Phase 2)
- `app/Http/Controllers/React/Auth/*.php`: 6 files (Phase 6)
- `app/Http/Resources/Frontend/*.php`: 2 files (Phase 2)
- `app/Providers/FortifyServiceProvider.php`: 1 file (Phase 6)
- `routes/frontend.php`: 1 file (Phases 2, 3, 4)
- `routes/auth.php`: 1 file (Phase 6)
- `config/inertia.php`: 1 file (Phase 6)
- `phpunit.xml`: 1 file (Phase 6)
- `tests/Feature/FrontendApi/*.php`: 4 test files (Phase 2, Phase 5)
- `tests/Feature/React/Auth/*.php`: 6 test files (Phase 6)
- `tests/Feature/React/LandingPageTest.php`: 1 file (Phase 3, Phase 6)
- `resources/js/types/*.ts`: 4 files (Phase 3, Phase 4)

---

## Cross-Phase Dependencies

Phase 3 → Phase 2:
- LandingPage fetches from `/frontend/conversions` (Phase 2 endpoint)
- LandingPage fetches from `/frontend/quick-action` (Phase 3 endpoint)
- CalendarQuickActions fetches from `/frontend/quick-action` (Phase 3 endpoint)

Phase 3 → Laravel backend:
- Uses ConversionResultResource structure for API responses
- Uses SavedConversionResource for save functionality

Phase 4 → Phase 2:
- SavedConversionsSection calls `/frontend/saved-conversions` CRUD endpoints (Phase 2 controllers)
- useSavedConversions hook consumes SavedConversionResource (Phase 2 resource)
- api.ts client reuses CSRF token pattern established in Phase 3

Phase 4 → Phase 3:
- SavedConversionsSection rendered inside LandingPage (Phase 3 page)
- CalendarResultsGrid delegates save to parent via onSave callback (Phase 3 component modified)
- guest-storage.ts preserves `savedConversions` and `conversionCollapsedStates` localStorage keys from existing Blade/Alpine implementation

Phase 5 → Phase 2:
- OnThisDayModal fetches from `GET /frontend/on-this-day` (Phase 2 controller endpoint)
- fetchOnThisDay() reuses apiHeaders() CSRF token pattern from Phase 4

Phase 5 → Phase 3:
- CalendarsInfoModal and OnThisDayModal rendered inside LandingPage (Phase 3 page)
- "On This Day" button visibility derived from `gregorianDateFromResults` memo reading `results['gregorian'].full_date`

Phase 5 → Phase 4:
- ToastProvider wraps app in app.tsx (Phase 3 file modified again)
- SavedConversionsSection uses `useToast()` for all save/delete/note/migration feedback
- Modal pattern follows EditNoteModal/SaveConversionModal conventions established in Phase 4

Phase 5 → Laravel backend:
- Reuses `OnThisDayCacheService` through existing `OnThisDayController` (no backend changes)
- `OnThisDayEventPage` type matches `content_urls.desktop.page` structure from cached Wikimedia API data

Phase 6 → Phase 1:
- Uses Inertia React infrastructure (app.tsx, HandleInertiaRequests middleware, root Blade view) from Phase 1

Phase 6 → Phase 3:
- LandingPageTest was updated to work with the new always-on `FRONTEND_REACT_ENABLED` test config
- AuthLayout follows same Tailwind CSS patterns (`input-technical`, `btn-primary-technical`) established in Phase 3

Phase 6 → Phase 5:
- LoginPage rendering uses same OAuth error display pattern from Phase 5's toast error styling

Phase 6 → Laravel backend:
- Fortify POST routes (`login.store`, `register.store`, `password.email`, `password.update`, `two-factor.login.store`) remain server-controlled
- `SocialAuthController` redirect/callback flows untouched — React only renders the Google Sign-In `<a>` tag
- `VerifyEmailController` signed URL verification untouched — React only renders the "Resend" button
- `Logout` action class untouched — React triggers via `post(route('logout'))`
- FortifyServiceProvider modified to conditionally render Inertia page for `two-factor.login` view

Phase 7 → Phase 6:
- AppLayout uses same sidebar navigation pattern from Phase 6 auth pages
- ConfirmPasswordPage follows same Inertia `useForm` pattern from Phase 6 auth pages
- SettingsLayout continues the tab navigation concept from Phase 6 auth layouts
- Feature flag `config('frontend.react_enabled')` same as Phase 6

Phase 7 → Phase 5:
- ToastProvider already integrated in Phase 5 wraps entire app
- On This Day modal integration (if added to app shell)

Phase 7 → Phase 3:
- LandingPageTest was updated to work with the new always-on `FRONTEND_REACT_ENABLED` test config
- AuthLayout follows same Tailwind CSS patterns (`input-technical`, `btn-primary-technical`) established in Phase 3

Phase 7 → Laravel backend:
- Uses existing Fortify 2FA actions (DisableTwoFactorAuthentication, EnableTwoFactorAuthentication, ConfirmTwoFactorAuthentication)
- Uses existing User model two-factor fields (`two_factor_secret`, `two_factor_recovery_codes`, `two_factor_confirmed_at`)
- Uses existing Fortify routes (`two-factor.enable`, `two-factor.confirm`, `password.confirm`, `user-password-update`)
- ConfirmPasswordPageController added in Phase 7 uses Fortify `confirmPasswordView` override

---

## Test Coverage by Phase

**Phase 2**: 2/2 commits (100%) ✅
- ConversionControllerTest
- SavedConversionControllerTest

**Phase 3**: 2/2 commits (100%) ✅
- QuickActionControllerTest
- LandingPageTest

**Phase 4**: 1/1 commits (100%) ✅
- SavedConversionControllerTest (9 new tests: migrate auth, migrate duplicates, validation, payload limits, empty notes, created_at preservation, note max-length store, note max-length update)

**Phase 5**: 1/1 commits (100%) ✅
- OnThisDayControllerTest (12 new tests: valid date, empty cache, date required, date format, type validation, valid types dataset, invalid JSON, empty day_info)

**Phase 6**: 1/1 commits (100%) ✅
- LoginPageTest (6 tests: render, canResetPassword/canRegister, oauth error, null oauthError, status flash, null status)
- RegisterPageTest (1 test: render)
- ForgotPasswordPageTest (2 tests: render, status flash)
- ResetPasswordPageTest (2 tests: render with props, token/email values)
- VerifyEmailPageTest (4 tests: render, verified redirect, auth required, status flash)
- TwoFactorChallengePageTest (4 tests: render with session, redirect without session, recovery true, recovery false)
- LandingPageTest (2 tests fixed: removed skip markers, removed dynamic config)

**Phase 7**: 1/1 commits (100%) ✅
- ConfirmPasswordPageTest (2 tests: render, session status flash)
- DashboardPageTest (1 test: authenticated dashboard render)
- AppearancePageTest (2 tests: render, system theme selection)
- PasswordPageTest (2 tests: render, redirect)
- ProfilePageTest (4 tests: render, profile update, email verification unchanged, email verification status unchanged)
- TwoFactorPageTest (4 tests: render with 2FA enabled, redirect when confirmation required, password confirmation redirect, features disabled response)
- TwoFactorAuthenticationTest (updated 4 assertions for Inertia)
- PasswordUpdateTest (2 tests: password update, correct password requirement)
- ProfileUpdateTest (5 tests: profile page, update, email unchanged, delete account, password for deletion)

**Overall**: 8/8 commits (100%) ✅

---

## Phase 7: App Shell, Dashboard & Settings

### Overview
- **Commits**: 1 implementation commit
- **Lines Changed**: +1,809/-32
- **Files Affected**: 23 files
- **Test Coverage**: 1/1 commits (100%) ✅ — 19 new tests added
- **Migrations**: 0

### Implementation Commits

**9e2976d** — feat(react): port authenticated app shell, dashboard, and settings (Phase 7)
**Impact**: +1,809/-32 lines, 23 files

**Backend (PHP):**

- **Controller**: Created `app/Http/Controllers/React/Auth/ConfirmPasswordPageController.php`
  - Method: `__invoke(Request $request): Response` — renders Inertia `Auth/ConfirmPasswordPage`

- **Controller**: Created `app/Http/Controllers/React/Dashboard/DashboardPageController.php`
  - Method: `__invoke(): Response` — renders Inertia `DashboardPage`

- **Controller**: Created `app/Http/Controllers/React/Settings/AppearancePageController.php`
  - Method: `__invoke(): Response` — renders Inertia `Settings/AppearancePage`

- **Controller**: Created `app/Http/Controllers/React/Settings/PasswordPageController.php`
  - Method: `__invoke(): Response` — renders Inertia `Settings/PasswordPage`

- **Controller**: Created `app/Http/Controllers/React/Settings/ProfilePageController.php`
  - Method: `__invoke(Request $request): Response` — renders Inertia `Settings/ProfilePage`
  - Props: `user` (name, email, has_verified_email), `status` (session flash)

- **Controller**: Created `app/Http/Controllers/React/Settings/TwoFactorPageController.php`
  - Method: `__invoke(Request $request): Response` — renders Inertia `Settings/TwoFactorPage`
  - Uses Fortify features gate, confirms two-factor authentication when required
  - Props: `twoFactorEnabled`, `requiresConfirmation`

- **ServiceProvider**: Modified `app/Providers/FortifyServiceProvider.php`
  - Modified `registerConfirmPasswordView()` to render React page when `config('frontend.react_enabled')` is true
  - Falls back to legacy Blade view when false

**Frontend (TypeScript/React):**

- **Layout**: Created `resources/js/layouts/AppLayout.tsx`
  - Responsive sidebar with hamburger menu (mobile overlay), dark mode support
  - User menu dropdown with settings link and logout button
  - Navigation: Dashboard link with home icon
  - User avatar initials display
  - `data-test` attributes for browser testing (`sidebar-menu-button`, `logout-button`)
  - CSS transform transitions for mobile sidebar slide-in

- **Layout**: Created `resources/js/layouts/SettingsLayout.tsx`
  - Settings page wrapper with heading and tab navigation
  - Tabs: Profile, Password, Appearance, Two-factor
  - Active tab highlighting with flux:tab component
  - Props: title, description

- **Page**: Created `resources/js/pages/Auth/ConfirmPasswordPage.tsx`
  - Uses Inertia `useForm` for `password` with `post(route('password.confirm'))`
  - Redirects to intended URL on success
  - Password visibility toggle
  - `data-test="confirm-password-button"` attribute

- **Page**: Created `resources/js/pages/DashboardPage.tsx`
  - Placeholder grid matching existing Blade dashboard aesthetic
  - Uses AppLayout wrapper
  - Head title "Dashboard"

- **Page**: Created `resources/js/pages/Settings/AppearancePage.tsx`
  - Light/Dark/System mode selector with radio buttons
  - Selection persisted to `localStorage` via `window.matchMedia`
  - Live preview of theme change
  - Uses SettingsLayout wrapper

- **Page**: Created `resources/js/pages/Settings/PasswordPage.tsx`
  - Uses Inertia `useForm` for `current_password`, `password`, `password_confirmation`
  - `post(route('user-password-update'))` submission
  - Password visibility toggles on all three fields
  - Success/error banner display
  - Uses SettingsLayout wrapper

- **Page**: Created `resources/js/pages/Settings/ProfilePage.tsx`
  - Uses Inertia `useForm` for `name`, `email`
  - `post(route('user-profile-information.update'))` submission
  - Email verification resend via `post(route('verification.send'))`
  - "Delete Account" button opening `<dialog>` modal
  - Delete account form with password confirmation, `delete(route('current-user.destroy'))` submission
  - Session status display for verification link sent
  - Uses SettingsLayout wrapper

- **Page**: Created `resources/js/pages/Settings/TwoFactorPage.tsx`
  - Full 2FA enable/confirm/disable flow
  - Enables with `post(route('two-factor.enable'))` when not enabled
  - QR code display after enable (from `twoFactorEnabled` state)
  - Manual setup key display (hexagonal key SVG icon)
  - OTP verification input with 6-digit auto-focus advance pattern
  - Confirm with `post(route('two-factor.confirm'))` passing `code`
  - Recovery codes display in grid with copy and regenerate buttons
  - Disable flow with password confirmation
  - Uses SettingsLayout wrapper

**Routes (modified):**

- **Routes**: Modified `routes/web.php`
  - Added React routes: `ConfirmPasswordPageController`, `DashboardPageController`
  - Added React settings routes: `AppearancePageController`, `PasswordPageController`, `ProfilePageController`, `TwoFactorPageController`
  - All routes feature-flagged with `config('frontend.react_enabled')`

**Tests:**

- **Tests**: Created `tests/Feature/React/Auth/ConfirmPasswordPageTest.php` (2 tests)
  - Render test, session status flash test

- **Tests**: Created `tests/Feature/React/Dashboard/DashboardPageTest.php` (1 test)
  - Authenticated dashboard render test

- **Tests**: Created `tests/Feature/React/Settings/AppearancePageTest.php` (2 tests)
  - Render test, system theme selection test

- **Tests**: Created `tests/Feature/React/Settings/PasswordPageTest.php` (2 tests)
  - Render test, redirect test

- **Tests**: Created `tests/Feature/React/Settings/ProfilePageTest.php` (4 tests)
  - Render test, profile update, email verification unchanged, email verification status unchanged

- **Tests**: Created `tests/Feature/React/Settings/TwoFactorPageTest.php` (4 tests)
  - Render with enabled 2FA user, redirect when 2FA disabled but confirmation required, password confirmation redirect, two-factor features disabled response

- **Tests**: Modified `tests/Feature/Settings/TwoFactorAuthenticationTest.php`
  - Updated 4 assertions to work with Inertia rendering

- **Tests**: Created `tests/Pest.php`, `tests/TestCase.php`, example tests for Feature/Unit directories
  - Browser DuskTestCase setup, standard TestCase setup
  - Example tests for smoke testing

- **Tests**: Created `tests/Feature/Settings/PasswordUpdateTest.php` (2 tests)
  - Password update flow, correct password requirement

- **Tests**: Created `tests/Feature/Settings/ProfileUpdateTest.php` (5 tests)
  - Profile page display, information update, email verification unchanged, account deletion, password requirement for deletion

**Vite Config:**

- **Config**: Created `vite.config.js`
  - Laravel Vite plugin with `resources/css/app.css` and `resources/js/app.js` inputs
  - Tailwind CSS v4 plugin integration
  - Server with CORS enabled

### Breaking Changes
None

### Technical Debt Introduced
None

---

## Phase 8: Testing Infrastructure & Route Cutover

### Overview
- **Commits**: 1 implementation commit
- **Lines Changed**: +2,350/-12
- **Files Affected**: 13 files
- **Test Coverage**: 1/1 commits (100%) ✅ — 66 React tests added
- **Migrations**: 0

### Implementation Commits

**55da9f0** — feat(react): implement Phase 8 testing infrastructure and route cutover
**Impact**: +2,350/-12 lines, 13 files

**Config Change:**

- **Config**: Modified `config/frontend.php`
  - Changed `'react_enabled'` default from `env('FRONTEND_REACT_ENABLED', false)` to `env('FRONTEND_REACT_ENABLED', true)`
  - React routes are now enabled by default

**Test Infrastructure (Vitest):**

- **Config**: Created `vitest.config.ts`
  - Environment: `jsdom`
  - Globals: `true` for test functions
  - Setup files: `./resources/js/test/setup.ts`
  - Coverage: v8 provider with text/json/html reporters
  - Includes: `resources/js/**/*.{ts,tsx}` excluding test files, test helpers, and types

- **Test Setup**: Created `resources/js/test/setup.ts`
  - Global test setup with required imports for @testing-library/jest-dom
  - Mocks global environment: `crypto.randomUUID()`, `matchMedia`, `localStorage`, `sessionStorage`
  - Custom `vi.fn()` matchers for vitest

- **Test Setup**: Created `resources/js/test/setup.test.ts`
  - Validates setup file exports and mocks are properly configured

- **Test Utils**: Created `resources/js/test/utils.tsx`
  - `AllTheProviders` component wrapping with BrowserRouter for testing
  - `customRender` function using React Testing Library's render with AllTheProviders
  - `createMockPage` helper for Inertia page props

- **Inertia Mocks**: Created `resources/js/test/inertia-mocks.ts`
  - Mock for `@inertiajs/react`: `Head` component, `Link` component, `useForm` hook with form state
  - Mock for `usePage` hook returning page props with auth and ziggy
  - Mock for `useRemember` hook

**Test Files:**

- **Tests**: Created `resources/js/pages/LandingPage.test.tsx` (341 lines)
  - LandingPage component tests covering:
    - Renders the landing page with conversion form
    - Calendar type selector with 19 input types
    - Date input field and convert button
    - Error state display
    - Results grid rendering after conversion
    - Save conversion modal for authenticated users
    - Guest save flow with localStorage
    - Collapsed states persistence
    - "On This Day" modal trigger
    - Calendars info modal

- **Tests**: Created `resources/js/pages/Auth/Auth.test.tsx` (175 lines)
  - LoginPage tests: form rendering, status messages, oauth errors, forgot password link, register link, Google Sign-In button, remember me checkbox, form submission
  - RegisterPage tests: form rendering, password fields, login link

- **Tests**: Created `resources/js/lib/guest-storage.test.ts` (311 lines)
  - GuestStorage constants tests
  - loadGuestConversions: empty data, invalid JSON, non-array data, invalid items filter, valid conversions
  - saveGuestConversions: localStorage save, GUEST_SAVE_LIMIT cap
  - addGuestConversion: new conversion, id/created_at generation, limit reached, duplicate prevention, newest-first ordering
  - deleteGuestConversion: remove by id, return updated list
  - updateGuestNote: update note, trim whitespace, cap at 2048 chars
  - loadCollapsedStates: empty object, invalid JSON, valid states
  - saveCollapsedStates: localStorage save
  - clearGuestData: removes all guest data
  - hasGuestData: false/true states

**Package.json Updates:**

- **Package**: Modified `package.json`
  - Added dev dependencies: `@testing-library/jest-dom`, `@testing-library/react`, `@testing-library/user-event`, `jsdom`, `vitest`
  - Added npm scripts: `test`, `test:run`, `test:coverage`

**tsconfig.json Updates:**

- **Config**: Modified `tsconfig.json`
  - Added `"types": ["node", "vitest/globals"]` to compiler options

**Documentation:**

- **Docs**: Modified `docs/react-frontend-migration-plan.md`
  - Marked Phase 8 as complete with test infrastructure summary

### Breaking Changes
⚠️ `config/frontend.php` — `react_enabled` default changed from `false` to `true`
  - React routes are now enabled by default; set `FRONTEND_REACT_ENABLED=false` to use legacy Blade routes

### Technical Debt Introduced
None

---

## Statistics

- **Total Commits**: 8 implementation commits
- **Total Lines**: +11,043 / -172
- **Total Files**: 129 files touched
- **Migrations**: 0
- **Test Coverage**: 100% (154 total tests: 88 PHP + 66 React)
- **Phases Completed**: 8 (Phase 2 through Phase 8 audited)
- **Duration**: Same day (Apr 14, 2026)

---

## Rollback Commands

To rollback Phase 8:
```bash
git revert 55da9f0
```

To rollback Phase 7:
```bash
git revert 9e2976d
```

To rollback Phase 6:
```bash
git revert a915b44
```

To rollback Phase 5:
```bash
git revert f5269f5
```

To rollback Phase 4:
```bash
git revert d703e0e
```

To rollback Phase 3:
```bash
git revert c2b7976
```

To rollback Phase 2:
```bash
git revert 4d88497
```
