# Calendar System Integration Guide

This guide details the process for adding a new calendar system to EpochBridge.

## 1. Create a Calendar Service

All calendar logic is encapsulated in service classes that extend `App\Services\CalendarConversionService`.

-   Create a new service in `app/Services/`, e.g., `NewCalendarService.php`.
-   Implement the abstract methods:
    -   `fromGregorian(Carbon $date): array`: Converts a Gregorian date to your new calendar. Must return a structured array.
    -   `toGregorian(array $dateData): Carbon`: Converts your calendar's date back to Gregorian.
    -   `getKey(): string`: A unique kebab-case key (e.g., `new-calendar`).
    -   `getName(): string`: A display name (e.g., `New Calendar`).
    -   `getTags(): array`: An array of tags for filtering (e.g., `['ancient', 'lunar']`).
    -   `validateDate(int $year, int $month, int $day): bool`: Validates a date in your calendar's system.
    -   `getDaysInMonth(int $year, int $month): int`: Returns the number of days in a given month.

## 2. Register the Service

In `app/Services/DateTimeConverterService.php`, add your new service to the constructor and decide if it's a "fast" or "additional" calendar.

```php
// In DateTimeConverterService.php constructor
public function __construct()
{
    $this->calendarServices = [
        // ... existing services
        'new-calendar' => new NewCalendarService(),
    ];
}
```
If it's a fast-loading calendar, add its key to the `$fastCalendars` array. Otherwise, it will be lazy-loaded.

## 3. Create a Calendar Card Component

Create a new Blade component to display the results for your calendar.

-   Create `resources/views/components/calendars/new-calendar-card.blade.php`.
-   Use the `base-calendar-card` component and pass in props.
-   Design the card to display the data returned by your service's `fromGregorian` method.

```blade
@props(['data'])

<x-calendars.base-calendar-card
    title="New Calendar"
    :copyText="$data['full_date'] ?? ''"
    copyType="new_calendar"
    class="bg-gray-50 dark:bg-gray-950/30 border-gray-200 dark:border-gray-800"
>
    {{-- Your custom display logic here --}}
    <div class="text-6xl font-bold">{{ $data['day'] ?? 'N/A' }}</div>
    <div>{{ $data['month_name'] ?? 'N/A' }} {{ $data['year'] ?? 'N/A' }}</div>
</x-calendars.base-calendar-card>
```

The `calendar-results-grid.blade.php` component will automatically pick up and render this new card using `<x-dynamic-component>` as long as the filename matches the service key.

## 4. Add Input Type (Optional)

If you want to allow direct input in your new calendar's format:

1.  Add it to the `$inputTypes` array in `resources/views/livewire/landing.blade.php`.
2.  Add a case for it in `parseInputDate` in `app/Services/DateTimeConverterService.php`.
3.  Add validation rules and an input field in `resources/views/livewire/components/calendar-input-section.blade.php`.

## 5. Add Tests

-   Create a unit test for your new service in `tests/Unit/Services/`.
-   Add assertions to feature/browser tests to ensure it displays correctly.
