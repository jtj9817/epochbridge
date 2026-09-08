# Calendar System Integration Guide

This guide details the process for adding a new calendar system to EpochBridge, including both simple service implementations and the advanced adapter pattern with dependency injection.

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
        // ... existing services (resolved with app(...))
        'new-calendar' => app(NewCalendarService::class),
    ];
}
```
If it's a fast-loading calendar, add its key to the `$fastCalendars` array. Otherwise, it will be computed in the additional tier.

## 3. Frontend Card (React)

The React results grid renders one card per calendar key (`resources/js/components/calendars/CalendarResultsGrid.tsx`):

- By default, your calendar renders through `GenericCalendarCard`, which displays the standard fields (`full_date`, `year`, `month_name`, `day`, …) and a `disclaimer` if the data contains one — **no frontend work required** for standard output.
- Only if your calendar needs bespoke rendering: create `resources/js/components/calendars/NewCalendarCard.tsx` using `BaseCalendarCard` for the shared frame (title, copy button, `.sd-*` styling), add a mapping from your service key to the card in `CalendarResultsGrid`, and export it from `resources/js/components/calendars/index.ts`.

## 4. Add Input Type (Optional)

If you want to allow direct input in your new calendar's format:

1.  Add the key to `INPUT_TYPES` (and its `INPUT_TYPE_LABELS` entry) in `resources/js/types/calendar.ts`.
2.  Add a case for it in `parseInputDate` in `app/Services/DateTimeConverterService.php`.
3.  The input-type selector in `resources/js/components/forms/CalendarInputSection.tsx` picks the new option up from the shared type definitions.

## 5. Add Tests

-   Create a unit test for your new service in `tests/Unit/Services/`.
-   Add assertions to feature/browser tests to ensure it displays correctly.

---

## Advanced: Adapter Pattern with Dependency Injection

For complex calendar systems requiring multiple calculation strategies, configuration options, or external library abstraction, use the **Adapter Pattern**.

### When to Use the Adapter Pattern

Consider using the adapter pattern when:

- ✅ Calendar requires external library abstraction
- ✅ Multiple calculation strategies are needed (astronomical vs arithmetic)
- ✅ Complex configuration options affect behavior
- ✅ Caching/memoization improves performance significantly
- ✅ High test coverage with mocked dependencies is desired
- ✅ Future alternative implementations are anticipated

**Examples**: 
- French Republican calendar with proleptic support and Paris mean solar offset
- Chinese lunisolar calendar with astronomical calculations and ΔT corrections

### Advanced: Multi-Class Subsystem Architecture

For extremely complex calendars requiring astronomical calculations, consider a dedicated subsystem with multiple specialized classes.

#### When to Use Multi-Class Subsystem

Use this pattern when:

- ✅ Calendar requires complex astronomical calculations (Sun/Moon ephemerides)
- ✅ Multiple data structures needed for intermediate calculations
- ✅ Significant caching and performance optimization required
- ✅ Algorithms span multiple domains (astronomy, time corrections, calendar logic)
- ✅ Implementation benefits from separation of concerns

**Example**: Chinese lunisolar calendar with `ChineseLunisolarCalendar`, `AstronomyCalculator`, and `LunarMonth` classes

#### Implementation Structure

```
app/Services/
└── YourCalendarService.php          # Main service (orchestrator)
    └── YourCalendar/                # Dedicated subsystem
        ├── YourCalendarCalculator.php   # Core calendar logic
        ├── AstronomyHelper.php          # Astronomical calculations
        └── CalendarDataStructure.php    # Specialized data structures
```

**Chinese Calendar Example**:
```php
// app/Services/ChineseCalendarService.php (orchestrator)
class ChineseCalendarService extends CalendarConversionService
{
    private ChineseLunisolarCalendar $calendar;
    
    public function __construct(?ChineseLunisolarCalendar $calendar = null)
    {
        $this->calendar = $calendar ?? new ChineseLunisolarCalendar();
    }
    
    public function fromGregorian(Carbon $date): array
    {
        $components = $this->calendar->chineseDateFor($date);
        // Format and return data
    }
}

// app/Services/Chinese/ChineseLunisolarCalendar.php
class ChineseLunisolarCalendar
{
    private AstronomyCalculator $astronomy;
    private array $suiCache = [];
    
    public function chineseDateFor(CarbonInterface $gregorian): array
    {
        // Complex lunation logic using astronomy calculator
    }
}

// app/Services/Chinese/AstronomyCalculator.php
class AstronomyCalculator
{
    public function newMoonAtOrAfter(CarbonImmutable $moment): CarbonImmutable
    {
        // Jean Meeus algorithms with ΔT corrections
    }
    
    public function solarLongitudeAt(CarbonImmutable $moment): float
    {
        // Solar position calculations
    }
}

// app/Services/Chinese/LunarMonth.php
class LunarMonth
{
    public function __construct(
        public readonly int $chineseYear,
        public readonly int $number,
        public readonly bool $isLeap,
        public readonly CarbonImmutable $monthStart,
        public readonly CarbonImmutable $monthEnd,
    ) {}
    
    public function length(): int
    {
        return $this->monthStart->diffInDays($this->monthEnd);
    }
}
```

This architecture provides:
- **Separation of Concerns**: Calendar logic, astronomy, and data structures isolated
- **Testability**: Each class can be tested independently
- **Performance**: Dedicated caching strategies per component
- **Maintainability**: Complex algorithms organized by domain
- **Extensibility**: Easy to add alternative astronomy calculators

### Implementation Steps

#### Step 1: Define the Adapter Interface

Create an interface in `app/Contracts/Calendar/`:

```php
<?php
// app/Contracts/Calendar/YourCalendarAdapter.php

namespace App\Contracts\Calendar;

interface YourCalendarAdapter
{
    /**
     * Convert Julian Day Number to calendar date components
     */
    public function jdToYmd(int $julianDay): array;
    
    /**
     * Convert calendar date to Julian Day Number
     */
    public function ymdToJd(int $year, int $month, int $day): int;
    
    /**
     * Get calendar epoch start (if applicable)
     */
    public function jdStart(): ?int;
    
    /**
     * Get calendar epoch end (if applicable)
     */
    public function jdEnd(): ?int;
    
    /**
     * Determine if year is a leap year
     */
    public function isLeapYear(int $year): bool;
}
```

#### Step 2: Create Concrete Adapter Implementation

```php
<?php
// app/Support/Calendars/YourCalendarExtCalendarAdapter.php

namespace App\Support\Calendars;

use App\Contracts\Calendar\YourCalendarAdapter;
use Fisharebest\ExtCalendar\YourCalendar;

class YourCalendarExtCalendarAdapter implements YourCalendarAdapter
{
    private YourCalendar $calendar;
    private array $cache = [];
    
    public function __construct()
    {
        $this->calendar = new YourCalendar();
    }
    
    public function jdToYmd(int $julianDay): array
    {
        // Check cache first
        $cacheKey = "jd_{$julianDay}";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        // Perform conversion
        $result = $this->calendar->jdToYmd($julianDay);
        
        // Cache result for subsequent calls
        $this->cache[$cacheKey] = $result;
        
        return $result;
    }
    
    public function ymdToJd(int $year, int $month, int $day): int
    {
        $cacheKey = "ymd_{$year}_{$month}_{$day}";
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }
        
        $result = $this->calendar->ymdToJd($year, $month, $day);
        $this->cache[$cacheKey] = $result;
        
        return $result;
    }
    
    public function jdStart(): ?int
    {
        return $this->calendar->jdStart();
    }
    
    public function jdEnd(): ?int
    {
        return $this->calendar->jdEnd();
    }
    
    public function isLeapYear(int $year): bool
    {
        return $this->calendar->isLeapYear($year);
    }
}
```

#### Step 3: Implement Calendar Service with Dependency Injection

```php
<?php
// app/Services/YourCalendarService.php

namespace App\Services;

use App\Contracts\Calendar\YourCalendarAdapter;
use Carbon\Carbon;

class YourCalendarService extends CalendarConversionService
{
    public function __construct(
        private YourCalendarAdapter $adapter
    ) {}
    
    public function fromGregorian(Carbon $date): array
    {
        // Apply any necessary offsets or adjustments
        $adjustedDate = $this->applyCalendarOffset($date);
        
        // Convert to Julian Day Number
        $jd = gregoriantojd(
            $adjustedDate->month,
            $adjustedDate->day,
            $adjustedDate->year
        );
        
        // Use adapter for conversion
        [$year, $month, $day] = $this->adapter->jdToYmd($jd);
        
        return [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'month_name' => $this->getMonthName($month),
            'day_of_week' => $this->getDayOfWeek($jd),
            'display' => $this->formatDisplay($year, $month, $day),
            'full_date' => "{$year}-{$month}-{$day}",
        ];
    }
    
    private function applyCalendarOffset(Carbon $date): Carbon
    {
        // Apply calendar-specific offsets if configured
        if (config('calendars.your_calendar.apply_offset')) {
            return $date->copy()->addSeconds(
                config('calendars.your_calendar.offset_seconds')
            );
        }
        
        return $date;
    }
    
    public function getKey(): string
    {
        return 'your-calendar';
    }
    
    public function getName(): string
    {
        return 'Your Calendar';
    }
    
    public function getTags(): array
    {
        return ['historical', 'astronomical'];
    }
    
    // Implement remaining abstract methods...
}
```

#### Step 4: Register Adapter Binding in Service Provider

```php
<?php
// app/Providers/AppServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\Calendar\YourCalendarAdapter;
use App\Support\Calendars\YourCalendarExtCalendarAdapter;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind adapter interface to concrete implementation
        $this->app->bind(
            YourCalendarAdapter::class,
            YourCalendarExtCalendarAdapter::class
        );
    }
}
```

#### Step 5: Add Calendar Configuration

Create or update `config/calendars.php`:

```php
<?php

return [
    'your_calendar' => [
        'apply_offset' => env('YOUR_CALENDAR_APPLY_OFFSET', true),
        'offset_seconds' => env('YOUR_CALENDAR_OFFSET_SECONDS', 0),
        'allow_proleptic' => env('YOUR_CALENDAR_ALLOW_PROLEPTIC', false),
    ],
];
```

#### Step 6: Update DateTimeConverterService

Register the service in `DateTimeConverterService` constructor, but **resolve it from the container** to get dependency injection:

```php
public function __construct()
{
    $this->calendarServices = [
        // ... existing services
        'your-calendar' => app(YourCalendarService::class),  // Resolve from container
    ];
}
```

#### Step 7: Testing with Mocked Adapters

The adapter pattern makes testing much easier:

```php
<?php
// tests/Unit/Services/YourCalendarServiceTest.php

use App\Contracts\Calendar\YourCalendarAdapter;
use App\Services\YourCalendarService;
use Carbon\Carbon;

test('converts gregorian date correctly', function () {
    // Create a mock adapter
    $mockAdapter = Mockery::mock(YourCalendarAdapter::class);
    
    // Define expected behavior
    $mockAdapter->shouldReceive('jdToYmd')
        ->once()
        ->with(2459946)  // JD for 2023-01-01
        ->andReturn([1444, 4, 12]);  // Example calendar date
    
    // Inject mock into service
    $service = new YourCalendarService($mockAdapter);
    
    // Test conversion
    $result = $service->fromGregorian(Carbon::parse('2023-01-01'));
    
    expect($result)
        ->toHaveKey('year', 1444)
        ->toHaveKey('month', 4)
        ->toHaveKey('day', 12);
});

test('applies configuration offsets when enabled', function () {
    Config::set('calendars.your_calendar.apply_offset', true);
    Config::set('calendars.your_calendar.offset_seconds', 561);
    
    $service = app(YourCalendarService::class);
    $result = $service->fromGregorian(Carbon::parse('2023-01-01'));
    
    // Assert that conversion occurred with offset applied
    expect($result)->toBeArray();
});
```

### Benefits of the Adapter Pattern

- **Testability**: Easy to mock adapter for unit tests without external dependencies
- **Flexibility**: Swap calculation strategies (astronomical vs arithmetic) via configuration
- **Maintainability**: Decoupled from specific library implementations
- **Performance**: Per-instance memoization/caching without global state pollution
- **Extensibility**: Add alternative adapters without modifying service code

### Real-World Example: French Republican Calendar

The French Republican calendar implementation demonstrates this pattern:

- **Interface**: `app/Contracts/Calendar/FrenchRepublicanCalendarAdapter.php`
- **Adapter**: `app/Support/Calendars/FrenchRepublicanExtCalendarAdapter.php`
- **Service**: `app/Services/FrenchRepublicanService.php`
- **Configuration**: `config/calendars.php` with `apply_paris_offset` and `allow_proleptic` options
- **Tests**: `tests/Unit/Services/FrenchRepublicanServiceTest.php` with mocked adapters

This architecture enables:
- Paris mean solar offset (UT1 + 00:09:21) for historical accuracy
- Proleptic extension beyond the historical 1792-1805 range
- Easy testing with mocked adapters
- Future support for alternative calculation methods

## Summary

- **Simple calendars**: Use the basic service extension pattern
- **Complex calendars**: Use the adapter pattern with dependency injection
- **Always test**: Write comprehensive unit and feature tests
- **Follow conventions**: Maintain consistent naming and structure
