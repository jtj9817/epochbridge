# Component Testing Guide

This guide covers how EpochBridge tests its React components and the HTTP surface they talk to. It was rewritten for the React/Inertia frontend (the previous Livewire/Volt component-testing approach was retired with Livewire in September 2026).

## Testing Philosophy

Testing is split by layer, each with the tool that fits it:

| Layer | Tool | Location | What it covers |
| --- | --- | --- | --- |
| React components | Vitest + React Testing Library | `resources/js/**/*.test.tsx` | rendering, user interaction, callbacks |
| PHP services & models | Pest unit tests | `tests/Unit/` | calendar math, caching, model behavior |
| HTTP pages & JSON API | Pest feature tests | `tests/Feature/React/`, `tests/Feature/FrontendApi/`, `tests/Feature/Auth/` | Inertia pages, endpoint contracts, auth flows |
| End-to-end UX | Laravel Dusk | `tests/Browser/` | full flows in a real browser (saves, notes, toasts) |

## React Component Tests (Vitest + RTL)

Component tests live **colocated** with the component (e.g. `CalendarResultsGrid.tsx` and `CalendarResultsGrid.test.tsx`). They render the component with realistic props and assert on what the user sees — not implementation details.

Run them with:

```bash
npm run test:run        # vitest run (CI mode)
npm run test            # vitest watch mode
```

### Key Principles

1. **Query like a user**: select by accessible role/label/text (`getByRole`, `getByText`) rather than CSS classes.
2. **Test props-in, behavior-out**: pass props, fire events (`userEvent`), assert rendered output and that callbacks fired with the right arguments.
3. **Mock the network at the module boundary**: components receive data via props; hooks call `lib/api.ts` — mock what the test shouldn't really do, keep the component's own logic real.
4. **One concern per test**: name tests after the behavior ("renders every calendar key", "calls onSave with the note text").

### Example

```tsx
// resources/js/components/modals/EditNoteModal.test.tsx (illustrative shape)
import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { EditNoteModal } from './EditNoteModal'

it('calls onSave with the trimmed note', async () => {
  const onSave = vi.fn()
  render(<EditNoteModal open onSave={onSave} />)

  await userEvent.type(screen.getByRole('textbox'), '  my note  ')
  await userEvent.click(screen.getByRole('button', { name: /save/i }))

  expect(onSave).toHaveBeenCalledWith('my note')
})
```

Existing examples to copy from: `CalendarResultsGrid.test.tsx`, `CalendarQuickActions.test.tsx`, `CalendarSearchFilter.test.tsx`, `SaveConversionModal.test.tsx`, `OnThisDayModal.test.tsx`, `SavedConversionsSection.test.tsx`, `lib/guest-storage.test.ts`.

## Feature Tests (Pest)

Inertia pages are tested like any Laravel feature: make the request, assert the Inertia page + props. The JSON API is tested with `postJson`/`getJson` against the `/frontend/*` routes, including validation edges (payload bounds, note length, ownership).

```php
// tests/Feature/FrontendApi/ConversionControllerTest.php (illustrative shape)
it('converts a gregorian date', function () {
    $this->postJson('/frontend/conversions', [
        'date' => '2024-01-15',
        'input_type' => 'gregorian',
    ])->assertSuccessful();
});
```

Auth flows (login, registration, password reset, email verification, 2FA, rate limiting, Google OAuth) live under `tests/Feature/Auth/`; Inertia page render tests under `tests/Feature/React/` mirror the controller layout (`React/Auth/`, `React/Settings/`, `React/Dashboard/`, `React/LandingPageTest.php`).

## Browser Tests (Dusk)

Dusk covers flows that only make sense end-to-end: guest saves persisting across reloads, note management, toast feedback. Tests live in `tests/Browser/` with page objects under `tests/Browser/Pages/`.

```php
// tests/Browser/SavedConversionsTest.php (illustrative shape)
public function test_guest_save_and_delete(): void
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->type('date-input', '2024-01-15')
            ->press('Convert')
            ->waitForText('Gregorian')
            ->press('Save');
    });
}
```

Run them with `php artisan dusk` once Sail is up. Prefer stable selectors (IDs, names, `dusk:` attributes); the React UI re-renders asynchronously, so pair assertions with `waitForText` / `waitUntilMissing`.

## What goes where — decision rules

- Pure rendering or callback logic → **Vitest component test** (fast, no Laravel boot).
- Validation, authorization, throttling, Eloquent behavior → **Pest feature/unit test**.
- Anything where the browser itself is part of the contract (localStorage, toasts, focus) → **Dusk browser test**.
