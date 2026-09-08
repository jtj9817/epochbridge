# Guest Conversions Guide

## Overview

EpochBridge gives guest (unauthenticated) users a capped, local-only version of saved conversions: up to **3** conversions are persisted in the browser's `localStorage` and are automatically migrated to the database when the guest registers or logs in. This guide describes the current React implementation (`resources/js/lib/guest-storage.ts`, `resources/js/hooks/useGuestSavedConversions.ts`, and the `/frontend/saved-conversions/migrate` endpoint).

## Architecture

### Data Flow

```
Guest journey:
  1. Guest performs a date conversion (POST /frontend/conversions)
  2. Saves it via SaveConversionModal (optional note, ≤2048 chars)
  3. Conversion stored in localStorage under 'savedConversions' (max 3)
  4. Rendered client-side by SavedConversionsSection; persists across sessions

Migration on login:
  1. Guest registers/logs in
  2. useGuestSavedConversions detects pending items
  3. POST /frontend/saved-conversions/migrate (≤3 items, validated + deduped)
  4. Server returns { success, migrated_count }
  5. Client clears localStorage ('savedConversions' + 'conversionCollapsedStates')
```

## Frontend Storage

**Module**: `resources/js/lib/guest-storage.ts`

| Constant | Value | Purpose |
| --- | --- | --- |
| `GUEST_SAVED_CONVERSIONS_KEY` | `savedConversions` | localStorage key for the items array |
| `GUEST_COLLAPSED_STATES_KEY` | `conversionCollapsedStates` | per-item collapsed UI state |
| `GUEST_SAVE_LIMIT` | `3` | maximum guest saves |

### Stored item shape

```ts
// resources/js/types/saved-conversion.ts — GuestSavedConversion
{
  id: number            // Date.now() at save time
  original_input: string
  conversion_data: { /* full multi-calendar result */ }
  note?: string         // trimmed, sliced to 2048 chars client-side
  created_at: string    // ISO timestamp
}
```

### API surface

- `loadGuestConversions()` — parses localStorage, defensively filters malformed entries, returns `[]` on any error
- `addGuestConversion(item)` — returns `null` (no-op) when the 3-item cap is reached or `original_input` is a duplicate of an existing save; otherwise prepends and persists
- `deleteGuestConversion(id)` — removes by id and persists
- `updateGuestNote(id, note)` — trims and slices the note to 2048 chars, persists
- `hasGuestData()` / `clearGuestData()` — migration helpers; `clearGuestData` removes both keys

## Backend Migration

**Route**: `POST /frontend/saved-conversions/migrate` (auth required — `routes/frontend.php`)

**Request validation** (`app/Http/Requests/Frontend/MigrateGuestConversionsRequest`):

| Field | Rules |
| --- | --- |
| `items` | required, array, **max 3** |
| `items.*.original_input` | required, string, max 255 |
| `items.*.conversion_data` | required, array, max 128 keys, ≤64 KiB (`MaxPayloadBytes`) |
| `items.*.note` | nullable, string, max 2048 |
| `items.*.created_at` | nullable, string |

**Handler** (`SavedConversionController::migrate`): runs in a database transaction; skips items whose `original_input` already exists for the user (server-side dedupe); denormalizes `gregorian_date`, `jalali_date`, `unix_timestamp`, `iso8601_string` from the conversion data; returns `{ success: true, migrated_count: n }`, or a 500 with a generic error (rolled back) on failure — the client keeps its localStorage payload so the migration can be retried.

## Database Schema (authenticated saves)

Guest conversions are **not** stored server-side; only authenticated saves are. See [architecture-overview.md](architecture-overview.md#database-architecture) for the full `saved_conversions` schema (unique `(user_id, original_input)`, listing indexes, note column). The authenticated store endpoint (`StoreSavedConversionRequest`) applies the same per-item bounds as migration.

## User Experience Flow

- **Guest**: full conversion functionality; up to 3 saves with optional notes, kept in the browser; delete supported; no server round-trips for reads
- **On login**: pending items migrate transparently, duplicates (same `original_input`) are skipped, local data is cleared, and the full database-backed history (paginated 20/page, searchable) takes over

## Testing

- **Unit (Vitest)**: `resources/js/lib/guest-storage.test.ts` covers load/save/add caps, duplicates, malformed storage, note trimming
- **Feature (Pest)**: `tests/Feature/FrontendApi/SavedConversionControllerTest.php` and `tests/Feature/DataMigrationTest.php` cover the migrate endpoint contract (validation, dedupe, auth)
- **Browser (Dusk)**: `tests/Browser/SavedConversionsTest.php` covers guest save/persist/delete end-to-end

## Security Considerations

- The migrate endpoint requires an authenticated session and sends the CSRF token (`X-CSRF-TOKEN` header set by `resources/js/lib/api.ts`)
- Payloads are bounded (≤3 items, ≤64 KiB each, ≤128 keys, note ≤2048) to prevent storage abuse
- No PII beyond the conversion data itself is stored locally; `clearGuestData()` wipes both keys after successful migration
