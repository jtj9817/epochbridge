# Calendar Filter Feature Fix Plan

## Problem Statement

The calendar filter feature is not working properly. While the search functionality works correctly, clicking filter checkboxes (Modern, Historical, Ancient, etc.) does not filter the displayed calendar results. The event is dispatching correctly, but filtered results are not being displayed.

## Root Cause Analysis

After analyzing the codebase, the issue is identified as a **reactivity/data flow problem** between Livewire components:

1. **Event Chain Works**: `calendar-search-filter` correctly dispatches `filters-updated` events
2. **Parent Receives Events**: `landing` component receives and stores filter state
3. **Child Prop Binding Fails**: `calendar-results-grid` uses reactive props but doesn't re-render when parent state changes
4. **Data Structure**: Calendar services correctly return `tags` arrays for filtering

The core problem is that **reactive prop binding between parent and child Livewire components is unreliable** for real-time updates. Livewire best practice is to use **event-driven communication** instead.

## Solution Architecture

Switch from reactive prop-based communication to event-driven communication:

```
calendar-search-filter (fires event)
    ↓
landing (receives, stores, re-broadcasts)
    ↓
calendar-results-grid (listens directly)
```

Instead of passing props that may not trigger reactivity, the parent component re-broadcasts events that child components listen to directly.

---

## Implementation Phases

### PHASE 1: Verify Event Dispatch & Data Structure

**GOAL:** Confirm that the filter component is correctly dispatching events with proper data structure

**CONTEXT:** The `calendar-search-filter` component uses Livewire's `updated()` hook to dispatch `filters-updated` events when checkboxes are clicked. Need to verify this is working correctly.

**TASK:** Add debugging and verify event dispatch

**REQUIREMENTS:**
1. Verify checkbox state updates trigger the `updated()` hook
2. Confirm event payload contains correct `selectedTypes` array
3. Ensure calendar data has `tags` field for filtering

**GUIDELINES:**

**File: `resources/views/livewire/components/calendar-search-filter.blade.php`**
- Add temporary console logging in Alpine.js to verify checkbox clicks
- Add `$wire.on('filters-updated', ...)` listener to confirm event dispatch
- Verify `selectedTypes` array structure when checkboxes are clicked

**File: `app/Services/DateTimeConverterService.php`**
- Verify lines 121-122 and 150-151 are correctly adding `tags` and `name` to all calendar results
- Check that `getTags()` method exists on all calendar services

---

### PHASE 2: Fix Reactivity in calendar-results-grid Component

**GOAL:** Ensure the results grid component properly reacts to prop changes from parent

**CONTEXT:** The `calendar-results-grid` component uses `->reactive()` modifier but may not be properly re-computing filtered results when `selectedTypes` prop changes from parent.

**TASK:** Replace reactive props with event listener pattern

**REQUIREMENTS:**
1. Remove reactive prop binding and use direct event listeners
2. Add explicit state update handlers for filter events
3. Ensure computed properties recalculate when state changes

**GUIDELINES:**

**File: `resources/views/livewire/components/calendar-results-grid.blade.php`**

**Remove** lines 6-12 (current reactive state definition)

**Add** direct event listener using `on()` helper:
```php
state([
    'conversionResults' => [],
    'conversionError' => '',
    'searchTerm' => '',
    'selectedTypes' => [],
    'dateInput' => '',
]);

on([
    'filters-updated' => function (string $searchTerm, array $selectedTypes) {
        $this->searchTerm = $searchTerm;
        $this->selectedTypes = $selectedTypes;
    },
    'conversion-updated' => function (array $results, string $dateInput) {
        $this->conversionResults = $results;
        $this->dateInput = $dateInput;
    },
]);
```

This makes the component listen directly to events instead of relying on reactive props.

**File: `resources/views/livewire/landing.blade.php`**

**Modify** lines 59-62 (existing filters-updated handler) to also dispatch to child:
```php
'filters-updated' => function (string $searchTerm, array $selectedTypes) {
    $this->searchTerm = $searchTerm;
    $this->selectedTypes = $selectedTypes;
    $this->dispatch('filters-updated', searchTerm: $searchTerm, selectedTypes: $selectedTypes);
},
```

**Add** dispatch after conversion (line 90, inside convertDate function):
```php
$this->conversionResults = $converter->getFullConversionResults($this->dateInput, $this->inputType);
$this->dispatch('conversion-updated', results: $this->conversionResults, dateInput: $this->dateInput);
```

**Remove** prop binding from calendar-results-grid component (lines 320-327), replace with:
```php
<livewire:components.calendar-results-grid wire:key="results-grid" />
```

---

### PHASE 3: Update Component Initialization

**GOAL:** Ensure calendar-results-grid initializes with correct data on page load

**CONTEXT:** After removing prop binding, the results grid needs initial data when conversions exist

**TASK:** Add mount() hook to receive initial data from parent

**REQUIREMENTS:**
1. Add mount hook to calendar-results-grid
2. Dispatch initial data from landing component after conversion
3. Handle both initial load and subsequent updates

**GUIDELINES:**

**File: `resources/views/livewire/components/calendar-results-grid.blade.php`**

**Add** mount hook after state definition:
```php
mount(function () {
    // Initialize with empty state - will receive data via events
});
```

**File: `resources/views/livewire/landing.blade.php`**

**Modify** mount hook (lines 43-46) to dispatch initial conversion if exists:
```php
mount(function () {
    $this->dateInput = now()->format('Y-m-d');
    // Don't auto-convert on mount, wait for user action
});
```

---

### PHASE 4: Test Filter Types Match Calendar Tags

**GOAL:** Verify that filter type options align with calendar service tags

**CONTEXT:** The filter component offers types like 'modern', 'historical', 'ancient', etc. These must match the tags returned by calendar services.

**TASK:** Audit all calendar services and ensure consistent tagging

**REQUIREMENTS:**
1. Verify all calendar services implement `getTags()` method
2. Ensure filter types in calendar-search-filter match available tags
3. Add missing tags if needed

**GUIDELINES:**

**Files to check: All `app/Services/*CalendarService.php` files**
- Verify each has `getTags()` method returning array
- Ensure tags match filter options: 'modern', 'historical', 'ancient', 'east-asian', 'lunar', 'solar', 'lunisolar', 'system'

**File: `resources/views/livewire/components/calendar-search-filter.blade.php`**
- Verify types array (lines 8-17) covers all calendar tags
- Add missing types if any calendar service uses tags not in the filter list

**Example tag assignments (verify/update):**
- **Gregorian, Jalali**: `['modern', 'solar']`
- **Hebrew**: `['modern', 'lunisolar']`
- **Islamic**: `['modern', 'lunar']`
- **Chinese**: `['east-asian', 'lunisolar']`
- **Japanese**: `['east-asian', 'solar']`
- **Coptic, Ethiopian, Egyptian**: `['historical', 'solar']`
- **French Republican**: `['historical', 'solar']`
- **Julian**: `['historical', 'solar']`
- **Babylonian, Assyrian, Macedonian**: `['ancient', 'lunar']` or `['ancient', 'lunisolar']`
- **Mayan**: `['ancient', 'solar']`
- **Unix, ISO8601, JD, MJD**: `['system']` (technical formats)

---

### PHASE 5: Update Tests

**GOAL:** Add comprehensive tests for filter functionality

**CONTEXT:** Current tests only verify event dispatch, not the complete filter workflow

**TASK:** Add integration tests for end-to-end filtering

**REQUIREMENTS:**
1. Test that clicking filters updates results grid
2. Test that multiple filters work together
3. Test that search + filters work together

**GUIDELINES:**

**File: `tests/Feature/Livewire/Components/CalendarSearchFilterTest.php`**

**Add** test for filter interaction with results grid:
```php
it('filters calendar results when types are selected', function () {
    Livewire::test('landing')
        ->call('convertDate')
        ->assertSee('Gregorian')
        ->dispatch('filters-updated', searchTerm: '', selectedTypes: ['ancient'])
        ->assertDontSee('Gregorian')
        ->assertSee('Babylonian');
});

it('combines search and filter correctly', function () {
    Livewire::test('landing')
        ->call('convertDate')
        ->dispatch('filters-updated', searchTerm: 'chinese', selectedTypes: ['east-asian'])
        ->assertSee('Chinese')
        ->assertDontSee('Japanese');
});
```

**File: `tests/Feature/Livewire/Components/CalendarResultsGridTest.php` (create if doesn't exist)**
- Add tests for filteredResults computed property
- Test search term filtering
- Test type filtering
- Test combined filtering

---

### PHASE 6: Add Debug Mode & Logging

**GOAL:** Add temporary debugging to verify event flow during development

**CONTEXT:** Need to confirm events are flowing correctly between components

**TASK:** Add console logging and wire:loading indicators

**REQUIREMENTS:**
1. Add Alpine.js console logs for debugging
2. Add visual feedback when filters are applied
3. Add wire:loading states

**GUIDELINES:**

**File: `resources/views/livewire/components/calendar-search-filter.blade.php`**

**Add** x-on directive to checkboxes for debugging:
```blade
<input 
    type="checkbox" 
    wire:model.live="selectedTypes" 
    value="{{ $key }}" 
    @change="console.log('Filter changed:', $event.target.value, $event.target.checked)"
    class="...">
```

**File: `resources/views/livewire/components/calendar-results-grid.blade.php`**

**Add** loading indicator during filtering:
```blade
<div wire:loading wire:target="searchTerm,selectedTypes" class="...">
    Filtering results...
</div>
```

**Add** Alpine.js watcher:
```blade
x-data="{ 
    searchTerm: @entangle('searchTerm').live,
    selectedTypes: @entangle('selectedTypes').live 
}"
x-init="
    $watch('searchTerm', value => console.log('Search term changed:', value));
    $watch('selectedTypes', value => console.log('Selected types changed:', value));
"
```

---

## Technical Details

### Current Architecture (Broken)

```php
// landing.blade.php
<livewire:components.calendar-results-grid
    :conversionResults="$conversionResults"
    :searchTerm="$searchTerm"
    :selectedTypes="$selectedTypes"
/>

// calendar-results-grid.blade.php
state([
    'searchTerm' => '',
    'selectedTypes' => [],
])->reactive();
```

**Problem**: Reactive props don't reliably trigger component updates in Livewire 3.

### Fixed Architecture (Event-Driven)

```php
// landing.blade.php
on([
    'filters-updated' => function (string $searchTerm, array $selectedTypes) {
        $this->searchTerm = $searchTerm;
        $this->selectedTypes = $selectedTypes;
        $this->dispatch('filters-updated', searchTerm: $searchTerm, selectedTypes: $selectedTypes);
    },
]);

<livewire:components.calendar-results-grid wire:key="results-grid" />

// calendar-results-grid.blade.php
on([
    'filters-updated' => function (string $searchTerm, array $selectedTypes) {
        $this->searchTerm = $searchTerm;
        $this->selectedTypes = $selectedTypes;
    },
]);
```

**Solution**: Parent re-broadcasts events that children listen to directly.

---

## Summary

The core issue is that **reactivity between parent and child Livewire components isn't working reliably with the current prop-based approach**. The solution is to:

1. **Switch from reactive props to event-driven communication** (Phase 2)
2. **Have the parent component re-broadcast events** that children can listen to directly (Phase 2)
3. **Ensure all calendar services have proper tags** that match filter options (Phase 4)
4. **Add comprehensive tests** to prevent regression (Phase 5)

This follows Laravel/Livewire best practices where **sibling components communicate via events through the parent**, rather than relying on reactive prop binding which can have timing issues.

### Key Laravel/Livewire Principles

1. **Event-Driven Communication**: Use `$this->dispatch()` and `on()` for component communication
2. **Computed Properties**: Use `computed()` for derived state that updates automatically
3. **Stateless Children**: Child components should listen to events, not receive props
4. **Parent Orchestration**: Parent components coordinate state and broadcast to children

### Testing Strategy

- **Unit Tests**: Test individual computed properties (filteredResults, scalarResults)
- **Integration Tests**: Test complete filter workflow from checkbox click to display update
- **Browser Tests**: Test actual user interactions with Dusk (optional but recommended)

---

## References

- **Architecture Documentation**: `docs/architecture-overview.md`
- **Livewire Events Documentation**: https://livewire.laravel.com/docs/events
- **Livewire Computed Properties**: https://livewire.laravel.com/docs/computed-properties
- **Test Files**: 
  - `tests/Feature/Livewire/Components/CalendarSearchFilterTest.php`
  - `tests/Feature/Livewire/Components/CalendarResultsGridTest.php`
