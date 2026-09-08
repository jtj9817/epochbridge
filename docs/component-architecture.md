# EpochBridge Component Architecture

This document describes the React component architecture of the EpochBridge landing page. It replaced the Livewire/Volt component system in April 2026; the Livewire runtime was removed entirely in September 2026.

## Overview

The architecture follows a "smart page, presentational children" pattern. `LandingPage` (`resources/js/pages/LandingPage.tsx`) owns the application state and data flow, while child components render specific UI sections and report user interactions back up through callbacks.

### Component Hierarchy

```
- LandingPage.tsx (state owner)
  ├── forms/CalendarInputSection.tsx        (date input + input-type selection)
  ├── forms/CalendarQuickActions.tsx        (Today / Yesterday / Tomorrow)
  ├── forms/CalendarSearchFilter.tsx        (search + calendar-type filtering)
  ├── calendars/CalendarResultsGrid.tsx     (conversion results)
  │   ├── calendars/GregorianCard.tsx       (specialized cards)
  │   ├── calendars/UnixCard.tsx
  │   ├── calendars/Iso8601Card.tsx
  │   ├── calendars/JulianDayCard.tsx
  │   ├── calendars/ModifiedJulianDayCard.tsx
  │   ├── calendars/GenericCalendarCard.tsx (fallback card for all other systems)
  │   └── calendars/BaseCalendarCard.tsx    (shared card frame: title, copy button)
  ├── saved-conversions/SavedConversionsSection.tsx
  │   └── saved-conversions/SavedConversionItem.tsx
  ├── modals/SaveConversionModal.tsx        (save with optional note)
  ├── modals/EditNoteModal.tsx              (note editing)
  ├── modals/OnThisDayModal.tsx             (Wikimedia events)
  ├── modals/CalendarsInfoModal.tsx         (calendar system reference)
  └── providers/ToastProvider.tsx           (app-level toast context)
```

## State Management & Communication

Communication follows **props down, callbacks up**:

1. **Page owns state**: `LandingPage` holds the primary state — `dateInput`, `inputType`, `results`, `error`, `isConverting`, `searchTerm`, `selectedTypes`, and modal open/close flags.
2. **Props down**: children are presentational and receive data plus callbacks through explicit `*Props` interfaces (e.g. `CalendarResultsGridProps`, `CalendarInputSectionProps`).
3. **Callbacks up**: children never mutate page state directly — they invoke callbacks (`onDateChange`, `onConvert`, etc.) that `LandingPage` passes down.

### Example: converting a date

1. **User action**: the user submits the form in `CalendarInputSection`.
2. **Callback up**: the section calls its `onConvert` callback with the current input.
3. **API call**: `LandingPage` sets `isConverting`, then `fetch`es `POST /frontend/conversions` with `{ date, input_type }` (CSRF header from the page meta tag; see `resources/js/lib/api.ts` for the shared client).
4. **State update**: results land in `results`; the grid re-renders all calendar cards.

### Data hooks

Persistence is delegated to hooks so components stay presentational:

- `useSavedConversions(authenticated)` — index/store/delete/note operations against `/frontend/saved-conversions`
- `useGuestSavedConversions` — localStorage-backed guest saves (key `savedConversions`, max 3, dedupe by `original_input`; see [guest-conversions-guide.md](guest-conversions-guide.md))
- `useOnThisDay` — `/frontend/on-this-day` for the current date

## Calendar Cards

`CalendarResultsGrid` maps each result key to a card:

- Specialized cards exist for **Gregorian**, **Unix**, **ISO 8601**, **Julian Day**, and **Modified Julian Day**.
- Every other calendar system renders through `GenericCalendarCard` with the calendar key and data.
- All cards share `BaseCalendarCard`, which provides the frame (title, copy-to-clipboard affordance) and the `.sd-*` Stardew styling.

Adding a new calendar system is covered in [calendar-integration-guide.md](calendar-integration-guide.md).

## Layouts & Providers

- **Layouts**: `AppLayout` (authenticated shell), `AuthLayout` (auth pages), `SettingsLayout` (settings pages) — selected by the Inertia page controllers under `app/Http/Controllers/React/`.
- **ToastProvider**: React context providing `addToast(type, message)` with `success` / `error` / `info` variants and a 4-second auto-dismiss.

## Testing

Components are tested with Vitest + React Testing Library via colocated `*.test.tsx` files (e.g. `CalendarResultsGrid.test.tsx`, `CalendarQuickActions.test.tsx`, `SaveConversionModal.test.tsx`); page-level and API behavior is covered by Pest feature tests (`tests/Feature/React/`) and Dusk browser tests (`tests/Browser/`). See [component-testing-guide.md](component-testing-guide.md).
