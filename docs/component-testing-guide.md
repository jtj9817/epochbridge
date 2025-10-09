# Component Testing Guide

This guide outlines our strategy for testing the refactored Livewire components.

## Testing Philosophy

We use a combination of **Feature** and **Browser** tests to cover our TALL stack components.

-   **Feature Tests (Pest/PHPUnit)**: Ideal for testing the server-side logic of Livewire components. We use these to test actions, state changes, event emissions, and validation logic.
-   **Browser Tests (Dusk)**: Essential for testing the full user experience, including JavaScript-driven interactions (Alpine.js) and client-side events.

Unit tests are reserved for complex, isolated PHP classes like our calendar services.

## Feature Testing Livewire Components

All component feature tests are located in `tests/Feature/Livewire/Components/`.

### Key Principles

1.  **Test Component Boundaries**: For child components, focus on testing that they correctly dispatch events in response to user actions.
2.  **Test Parent Listeners**: For the parent component (`landing`), test that it correctly responds to events from children and updates its state.
3.  **Simulate Actions**: Use `->call('actionName', ...)` to trigger methods on the component.
4.  **Assert State**: Use `->assertSet('propertyName', $expectedValue)` to check component state.
5.  **Assert Events**: Use `->assertDispatched('event-name', ...)` to verify that events are emitted correctly.

### Example: Testing `CalendarQuickActions`

```php
// tests/Feature/Livewire/Components/CalendarQuickActionsTest.php

it('dispatches date-selected event for today', function () {
    $today = now()->format('Y-m-d');
    Volt::test('components.calendar-quick-actions', ['inputType' => 'gregorian'])
        ->call('setToday')
        ->assertDispatched('date-selected', date: $today);
});
```

## Browser Testing with Dusk

Dusk tests are in `tests/Browser/` and cover end-to-end user flows.

### Key Principles

1.  **Simulate Real User Behavior**: Interact with the page as a user would (clicking, typing, etc.).
2.  **Test Alpine.js Interactions**: Verify client-side functionality like search filtering, guest saves (localStorage), and copy-to-clipboard feedback.
3.  **Use Selectors**: Use stable selectors like IDs, names, or `dusk` attributes for reliable tests.
4.  **Wait for Elements**: Use `waitForText`, `waitUntilMissing`, etc., to handle the asynchronous nature of Livewire and JavaScript.

### Example: Testing Guest Saves

```php
// tests/Browser/LandingPageTest.php

public function test_guest_can_save_and_delete_conversions(): void
{
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->type('date-input-gregorian', '2024-01-01')
            ->press('Convert Date')
            ->waitForText('Conversion Results')
            ->press('Save') // Triggers Alpine.js function
            ->waitForText('Your Saved Conversions')
            ->assertSeeIn('#saved-conversions-guest', '2024-01-01')
            ->with('#saved-conversions-guest', function ($guestConversions) {
                $guestConversions->press('Delete');
            })
            ->waitUntilMissingText('2024-01-01');
    });
}
```
