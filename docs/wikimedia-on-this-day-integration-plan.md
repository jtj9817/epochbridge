# Wikimedia "On This Day" Integration Plan

## Overview
Integrate the Wikimedia Feed API "On this day" endpoint to display English Wikipedia historical highlights in a pop-up after a successful date conversion. The system must read from a local cache table first and only call the API when no cached entry exists for the converted date.

## Goals
- Show "On this day" content in a modal/popup immediately after a user completes a successful conversion.
- Cache API responses in a new database table keyed by month/day to avoid redundant requests.
- Ensure cache-first behavior: **only** call the API when no cached entry exists for the requested date.
- Keep the UI responsive with loading and error states that do not block conversions.

## Non-Goals
- No multi-language support (English-only for now).
- No scheduled refresh or TTL invalidation (cache remains until explicitly cleared).
- No anonymous API proxy endpoints beyond the app server (no public API exposure yet).

## API Summary (Wikimedia Feed API)
- Endpoint pattern: `/feed/v1/wikipedia/{language}/onthisday/{type}/{MM}/{DD}`
- Language: `en`
- Type: `all` (includes events, births, deaths, holidays, selected)
- Month/day: zero-padded (e.g., `01/07`)
- Auth: Bearer token
- Required headers: `Authorization: Bearer <token>` and `User-Agent`/`Api-User-Agent`

## Data Model & Storage
### New Table: `on_this_day_entries`
Cache the API response per month/day, language, and type.

**Proposed columns**:
- `id` (bigint, primary key)
- `language` (varchar, default `en`)
- `type` (varchar, default `all`)
- `month` (tinyint) — 1 to 12
- `day` (tinyint) — 1 to 31
- `payload` (json) — raw API response
- `fetched_at` (timestamp) — when the API response was stored
- `created_at`, `updated_at`

**Indexes**:
- Unique: `(language, type, month, day)`

## Backend Plan
### 1) Migration + Model
- Add migration: `create_on_this_day_entries_table` with the schema above.
- Add Eloquent model: `app/Models/OnThisDayEntry.php`.
- Add casts for `payload` as array and `fetched_at` as datetime.

### 2) Service Layer
Create `app/Services/Wikimedia/OnThisDayService.php` with cache-first logic.

**Responsibilities**:
- Accept a Gregorian date (from conversion results).
- Normalize month/day to zero-padded values.
- Look up cache:
  - `OnThisDayEntry::where('language', 'en')->where('type', 'all')->where('month', $month)->where('day', $day)->first()`
- If cached and payload is non-empty, return payload immediately.
- If not cached:
  - Call Wikimedia API using `Http::withHeaders()`.
  - Store payload in `on_this_day_entries`.
  - Return payload.

**Notes**:
- Log and surface API failures without blocking the conversion flow.
- Only store a row when the response is successful and contains expected keys.

### 3) Configuration
- Add config in `config/services.php`:
  - `wikimedia.base_url` (default `https://api.wikimedia.org`)
  - `wikimedia.token` (from `WIKIMEDIA_API_TOKEN`)
  - `wikimedia.user_agent` (from `WIKIMEDIA_USER_AGENT`)
- Document required `.env` entries in the plan and README if needed.

### 4) Integration Point
- In `resources/views/livewire/landing.blade.php`, after a successful conversion:
  - Use the Gregorian result (`conversionResults['gregorian']['full_date']`).
  - Parse month/day and call `OnThisDayService`.
  - Update Livewire state (`onThisDayPayload`, `showOnThisDayModal`, `onThisDayError`).

## Frontend Plan
### 1) Livewire State
Add new state to the landing component:
- `onThisDayPayload` (array)
- `onThisDayLoading` (bool)
- `onThisDayError` (string)
- `showOnThisDayModal` (bool)

### 2) Modal Component
Create `resources/views/livewire/components/on-this-day-modal.blade.php` (Volt).

**Behavior**:
- Opens automatically when conversion succeeds and payload is ready.
- Shows a loading state while the service runs.
- Shows an error callout if the API call fails.
- Renders sections for `selected`, `events`, `births`, `deaths`, `holidays`.

**UI Layout**:
- Modal header: "On This Day — {Month Day}".
- Section cards with:
  - Year
  - Event text
  - Link to the Wikipedia page (from `content_urls.desktop.page`)
- Limit initial items (e.g., 3 per section) and include "Show more" toggles.

### 3) Triggering the Popup
- When `convertDate` completes successfully:
  - Set `onThisDayLoading = true`.
  - Call `OnThisDayService` via Livewire.
  - Set payload + open modal.
  - Set `onThisDayLoading = false`.
- Avoid re-opening if the user re-filters results without changing date.

## Data Flow (Cache-First)
1. User converts a date.
2. Conversion results include Gregorian date.
3. Service checks `on_this_day_entries` for `{month, day, language, type}`.
4. If found: return cached payload.
5. If missing: call Wikimedia API, store payload, return it.
6. UI shows modal with results.

## Error Handling
- If API fails:
  - Log error with month/day + status code.
  - Show a friendly message in the modal.
  - Do not write an empty cache row.

## Testing Plan
### Backend
- Unit tests for `OnThisDayService`:
  - Returns cached payload without calling HTTP.
  - Calls HTTP and stores payload when cache is empty.
  - Handles non-200 responses gracefully.

### Feature
- Livewire test for landing page:
  - Successful conversion triggers modal state.
  - Cached response bypasses HTTP client.
  - Error state displays fallback message.

## Implementation Checklist
- [ ] Add migration + model for `on_this_day_entries`.
- [ ] Add Wikimedia service and config.
- [ ] Update Livewire landing conversion flow.
- [ ] Add modal component + UI wiring.
- [ ] Add tests (unit + feature).
- [ ] Document environment variables.
