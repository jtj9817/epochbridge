# Calendar Filter Feature Fix Plan

## Problem Statement

The calendar filter feature is not working properly. While the search functionality works correctly, clicking filter checkboxes (Modern, Historical, Ancient, etc.) does not filter the displayed calendar results. The event is dispatching correctly, but filtered results are not being displayed.

## Root Cause Analysis - CORRECTED

After analyzing the codebase and testing implementations, the issue is identified as:

1. **Event Chain Works**: `calendar-search-filter` correctly dispatches `filters-updated` events
2. **Parent Receives Events**: `landing` component receives and stores filter state
3. **Reactive Props Work Correctly**: `calendar-results-grid` uses `->reactive()` which DOES work
4. **Data Structure**: Calendar services correctly return `tags` arrays for filtering

### Initial Incorrect Diagnosis

The original plan suggested that **reactive prop binding is unreliable** and recommended switching to event-driven communication. This diagnosis was **INCORRECT**.

### Actual Root Cause

**Mixing event-driven and reactive prop patterns creates infinite loops:**

When you combine:
1. Event dispatching from child to parent (`filters-updated`)
2. Parent re-broadcasting events to children (`$this->dispatch()`)
3. Reactive props from parent to children (`->reactive()`)

You create a feedback loop:
```
Filter change → event to parent → parent state update → reactive props push to child → 
child re-renders → may trigger events → parent updates again → infinite loop
```

## Solution Architecture - CORRECTED

**Use ONLY reactive props with one-way data flow:**

```
calendar-search-filter (fires event)
    ↓
landing (receives, stores state ONLY)
    ↓
Props automatically flow to calendar-results-grid (reactive)
    ↓
Computed properties recalculate
    ↓
View updates
```

**Key principle: NO event re-broadcasting from parent to children.**

---

## Implementation - What Actually Works

### ✅ WORKING PATTERN: Reactive Props Only

**File: `landing.blade.php`**
```php
// Parent receives events and stores state
on([
    'filters-updated' => function (string $searchTerm, array $selectedTypes) {
        $this->searchTerm = $searchTerm;
        $this->selectedTypes = $selectedTypes;
        // Do NOT re-dispatch - causes infinite loop with reactive props
    },
]);

// Parent passes props to child
<livewire:components.calendar-results-grid
    :conversionResults="$conversionResults"
    :conversionError="$conversionError"
    :searchTerm="$searchTerm"
    :selectedTypes="$selectedTypes"
    :dateInput="$dateInput"
    wire:key="results-grid"
/>
```

**File: `calendar-results-grid.blade.php`**
```php
// Child receives props with reactive modifier
state([
    'conversionResults' => [],
    'conversionError' => '',
    'searchTerm' => '',
    'selectedTypes' => [],
    'dateInput' => '',
])->reactive();  // This DOES work correctly

// Computed properties automatically recalculate when reactive props change
$filteredResults = computed(function() use ($scalarResultKeys) {
    $searchTerm = strtolower((string) $this->searchTerm);
    $selectedTypes = is_array($this->selectedTypes) ? $this->selectedTypes : [];
    
    // Filter logic using searchTerm and selectedTypes
    $typeMatch = empty($selectedTypes) || ! empty(array_intersect($selectedTypes, $tags));
    // ...
});
```

**File: `calendar-search-filter.blade.php`**
```php
// Simple wire:model.live bindings trigger updated() hook
updated([
    'searchTerm' => fn () => $this->dispatch('filters-updated', searchTerm: $this->searchTerm, selectedTypes: $this->selectedTypes),
    'selectedTypes' => fn () => $this->dispatch('filters-updated', searchTerm: $this->searchTerm, selectedTypes: $this->selectedTypes),
]);

// Minimal Alpine for UI state only
<div x-data="{ showFilters: false }">
```

---

## ❌ BROKEN PATTERN: Event Re-Broadcasting

**DO NOT DO THIS:**

```php
// landing.blade.php - WRONG
on([
    'filters-updated' => function (string $searchTerm, array $selectedTypes) {
        $this->searchTerm = $searchTerm;
        $this->selectedTypes = $selectedTypes;
        $this->dispatch('filters-updated', ...);  // ❌ CAUSES INFINITE LOOP
    },
]);

$convertDate = function () {
    $this->conversionResults = $converter->getFullConversionResults(...);
    $this->dispatch('conversion-updated', ...);  // ❌ UNNECESSARY
};

// calendar-results-grid.blade.php - WRONG
on([
    'filters-updated' => function (...) { },  // ❌ NOT NEEDED
    'conversion-updated' => function (...) { },  // ❌ NOT NEEDED
]);
```

**Why this fails:**
- Creates circular event loops
- Reactive props already handle state updates automatically
- Combining both patterns causes conflicts

---

## Key Laravel/Livewire Principles - CORRECTED

### 1. One-Way Data Flow with Reactive Props

**Parent → Props → Child** (one direction only)

```php
// Parent stores state
state(['searchTerm' => '', 'selectedTypes' => []]);

// Parent passes to child via props
<livewire:child :searchTerm="$searchTerm" :selectedTypes="$selectedTypes" />

// Child receives with ->reactive()
state(['searchTerm' => '', 'selectedTypes' => []])->reactive();
```

### 2. Events Flow UP the Component Tree

**Child → Event → Parent** (upward communication)

```php
// Child dispatches event
$this->dispatch('filters-updated', searchTerm: $this->searchTerm);

// Parent listens
on(['filters-updated' => function($searchTerm) { $this->searchTerm = $searchTerm; }]);
```

### 3. DO NOT Mix Patterns

**❌ WRONG:** Events down + Props down = Infinite loop
**✅ RIGHT:** Events up + Props down = One-way flow

### 4. Alpine.js for Client-Side UI State Only

```php
// Good - UI state only
<div x-data="{ showFilters: false }">
    <button @click="showFilters = !showFilters">Toggle</button>
</div>

// Bad - Trying to manage Livewire state
<div x-data="{ searchTerm: @entangle('searchTerm') }">  // ❌ Unnecessary complexity
```

---

## Implementation Phases - SIMPLIFIED

### Phase 1: Use Reactive Props Pattern

**File: `calendar-results-grid.blade.php`**
- Keep `->reactive()` modifier on state
- Remove any `on()` event listeners
- Let computed properties automatically recalculate

### Phase 2: Parent Stores State Only

**File: `landing.blade.php`**
- Keep `on()` listeners to receive events from children
- Store state updates ONLY - no re-dispatching
- Pass state to children via props

### Phase 3: Clean Alpine Components

**File: `calendar-search-filter.blade.php`**
- Use `wire:model.live` for real-time updates
- Keep Alpine minimal for UI state only (`showFilters`)
- No complex Alpine state management

### Phase 4: Verify Calendar Tags

**File: `app/Services/DateTimeConverterService.php`**
- Ensure all calendar results include `tags` and `name` fields
- Verify `getTags()` method exists on all calendar services

---

## Technical Details

### Reactive Props in Livewire 3

Reactive props **DO work correctly** when used properly:

```php
// Parent component
state(['filter' => 'modern']);
<livewire:child :filter="$filter" />  // Prop passes down

// Child component
state(['filter' => ''])->reactive();  // Automatically updates when parent changes

// Computed properties recalculate automatically
$results = computed(function() {
    // Uses $this->filter which updates reactively
    return $this->allData->filter(fn($item) => $item->type === $this->filter);
});
```

### Why Infinite Loops Occur

```php
// Infinite loop pattern
Child: wire:model updates → dispatch event → Parent: receive event → update state → 
reactive props push → Child: receive props → trigger update → dispatch event → LOOP
```

### How to Avoid Loops

1. **Events go UP only** (child to parent)
2. **Props go DOWN only** (parent to child)
3. **Never re-broadcast** events from parent back to children
4. **Let reactive props handle** state synchronization automatically

---

## Testing Strategy

### Manual Testing Workflow

1. Convert a date → All calendars display
2. Open "Show Filters" → Checkboxes visible
3. Check "Modern" filter → Only Modern calendars show (Gregorian, Jalali)
4. Check "Ancient" filter → Modern + Ancient calendars show
5. Uncheck both → All calendars show again
6. Type "Chinese" in search → Only Chinese calendar shows
7. Clear search → All calendars show again

### Automated Testing

```php
// Test reactive props work
it('filters results when parent props change', function () {
    Livewire::test('landing')
        ->set('searchTerm', 'gregorian')
        ->assertSee('Gregorian')
        ->assertDontSee('Chinese');
});

// Test filter checkboxes
it('filters by calendar type', function () {
    Livewire::test('landing')
        ->set('selectedTypes', ['modern'])
        ->assertSee('Gregorian')
        ->assertDontSee('Babylonian');
});
```

---

## Summary

### What We Learned

1. **Reactive props DO work** in Livewire 3 - the original diagnosis was wrong
2. **Mixing patterns causes problems** - use either events OR props, not both directions
3. **One-way data flow is key** - events up, props down, never reverse
4. **Keep Alpine minimal** - use only for client-side UI state

### The Correct Pattern

```
User Input (Search/Filter)
    ↓
wire:model.live updates Livewire state
    ↓
updated() hook dispatches event ↑ (to parent)
    ↓
Parent receives event, stores state
    ↓
Reactive props automatically push ↓ (to children)
    ↓
Computed properties recalculate
    ↓
View updates
    ✓ (no loop back)
```

### Key Takeaway

**KISS Principle (Keep It Simple):** Use Livewire's built-in reactive props. They work correctly when you follow the one-way data flow pattern. Don't over-engineer with unnecessary event re-broadcasting.

---

## References

- **Architecture Documentation**: `docs/architecture-overview.md`
- **Alpine-Laravel Integration**: `docs/Alpine-Laravel-Doc.md`
- **Livewire Reactive Props**: https://livewire.laravel.com/docs/properties#reactive-props
- **Livewire Events**: https://livewire.laravel.com/docs/events (use for child-to-parent only)
- **Test Files**: 
  - `tests/Feature/Livewire/Components/CalendarSearchFilterTest.php`
  - `tests/Feature/Livewire/Components/CalendarResultsGridTest.php`
