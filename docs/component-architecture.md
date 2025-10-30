# EpochBridge Component Architecture

This document outlines the refactored component architecture for the EpochBridge landing page, built with Livewire Volt. The previous monolithic `landing.blade.php` has been decomposed into a more modular and maintainable structure.

## Overview

The architecture follows a "smart parent, dumb children" pattern. The main `landing` component acts as the orchestrator, managing the core application state, while child components are responsible for specific UI sections and emit events to communicate with the parent.

### Component Hierarchy

```
- landing.blade.php (Parent Orchestrator)
  ├── components/calendar-input-section.blade.php
  ├── components/calendar-quick-actions.blade.php
  ├── components/calendar-search-filter.blade.php
  ├── components/calendar-results-grid.blade.php
  │   ├── components/save-conversion-modal.blade.php (Save with notes)
  │   └── components/calendars/*.blade.php (Individual Calendar Cards)
  └── components/saved-conversions-section.blade.php
      ├── components/edit-note-modal.blade.php (Shared note editing)
      └── components/saved-conversion-item.blade.php
```

## State Management & Communication

Communication follows a clear pattern: **Props Down, Events Up**.

1.  **Parent Owns State**: The `landing` component holds the primary state, such as `dateInput`, `inputType`, `conversionResults`, and `conversionError`.
2.  **Props Down**: The parent passes necessary state down to child components as properties (props). For example, `inputType` is passed to `calendar-quick-actions`.
3.  **Events Up**: Child components do not modify the parent's state directly. Instead, they emit events with relevant data. The parent component listens for these events and updates its own state.

### Example: Updating the Date Input

1.  **User Action**: The user changes the date in `calendar-input-section`.
2.  **Event Emission**: The `updated` lifecycle hook in `calendar-input-section` dispatches an `input-updated` event with the new `dateInput` and `inputType`.
    ```php
    // In calendar-input-section.blade.php
    updated(['dateInput' => fn () => $this->dispatch('input-updated', ...)]);
    ```
3.  **Parent Listener**: The `landing` component has an `#[On('input-updated')]` attribute on a method, which catches the event.
    ```php
    // In landing.blade.php
    #[On('input-updated')]
    $updateInput = function (string $dateInput, string $inputType) {
        $this->dateInput = $dateInput;
        $this->inputType = $inputType;
    };
    ```
4.  **State Update**: The `$updateInput` function updates the state on the `landing` component, and Livewire re-renders the necessary components.

This one-way data flow makes the application easier to reason about and debug.
