# PHP Jalali Calendar Packages: Complete Technical Documentation

**For Laravel applications requiring Persian calendar support**, two packages provide robust solutions: **morilog/jalali** offers a feature-rich, Laravel-integrated Jalalian date library with extensive formatting and Carbon interoperability, while **fisharebest/ext-calendar** delivers a lightweight, framework-agnostic multi-calendar system library. Both use the high-accuracy Borkowski algorithm for date conversions valid from 1799-2256 CE.

This documentation covers complete API references, real-world implementation patterns, Laravel integration strategies, performance optimization, common pitfalls with solutions, and comprehensive troubleshooting guidance for building production-ready Persian calendar applications.

---

## Package Overview

### morilog/jalali

**Purpose**: Full-featured Jalali (Persian/Shamsi) calendar library with Laravel integration and Carbon compatibility.

**Key Statistics**:
- **Latest Version**: 3.4.2
- **PHP Requirements**: ≥7.0 or ≥8.0  
- **Laravel Support**: 6.x through 11.x
- **Dependencies**: nesbot/carbon (^1.21 || ^2.0 || ^3.0)
- **Algorithm**: Borkowski (1799-2256 CE accuracy)
- **Immutability**: ✅ Yes (v3.x)

**Installation**:
```bash
composer require morilog/jalali:3.*
```

### fisharebest/ext-calendar

**Purpose**: Pure PHP implementation of six calendar systems, providing drop-in replacement for PHP's ext/calendar extension.

**Key Statistics**:
- **Latest Version**: 2.6.0
- **PHP Requirements**: ≥5.3.0
- **Dependencies**: None
- **Supported Calendars**: Gregorian, Julian, Jewish, Arabic (Hijri), French Republican, Persian (Jalali)
- **Use Case**: Calendar conversions, genealogy software, systems without ext/calendar

**Installation**:
```bash
composer require fisharebest/ext-calendar:~2.5
```

---

## Complete API Reference: morilog/jalali

### Jalalian Class - Primary Date Manipulation

**Namespace**: `Morilog\Jalali\Jalalian`

#### Constructor and Properties

**Private Properties**:
- `int $year` - Jalali year (1000-3000)
- `int $month` - Month number (1-12)
- `int $day` - Day of month (1-31)
- `int $hour` - Hour (0-24)
- `int $minute` - Minute (0-59)
- `int $second` - Second (0-59)
- `DateTimeZone $timezone` - Timezone object

**Constructor**:
```php
public function __construct(
    int $year,
    int $month,
    int $day,
    int $hour = 0,
    int $minute = 0,
    int $second = 0,
    ?DateTimeZone $timezone = null
)
```

**Example**:
```php
$date = new Jalalian(1399, 8, 6, 14, 30, 0, new DateTimeZone('Asia/Tehran'));
```

### Static Factory Methods

| Method | Parameters | Return | Description |
|--------|-----------|--------|-------------|
| `now()` | ?DateTimeZone | Jalalian | Current date/time |
| `fromCarbon()` | Carbon | Jalalian | Convert from Carbon |
| `fromFormat()` | string format, string timestamp, ?DateTimeZone | Jalalian | Parse formatted string |
| `fromDateTime()` | mixed dateTime, ?DateTimeZone | Jalalian | From DateTime/timestamp/string |
| `forge()` | mixed timestamp, ?DateTimeZone | Jalalian | Flexible factory (alias) |

**Examples**:
```php
$now = Jalalian::now();
$date = Jalalian::fromCarbon(Carbon::parse('2020-10-27'));
$parsed = Jalalian::fromFormat('Y/m/d', '1399/08/06');
$flexible = Jalalian::forge('yesterday');
```

### Instance Getter Methods

| Method | Return Type | Description |
|--------|-------------|-------------|
| `getYear()` | int | Jalali year |
| `getMonth()` | int | Month (1-12) |
| `getDay()` | int | Day of month |
| `getHour()` | int | Hour (0-23) |
| `getMinute()` | int | Minute (0-59) |
| `getSecond()` | int | Second (0-59) |
| `getTimezone()` | DateTimeZone | Timezone object |
| `getTimestamp()` | int | Unix timestamp |
| `getMonthDays()` | int | Days in current month |
| `getDaysOf()` | int | Days in specified month |
| `getDayOfWeek()` | int | Day of week (0=Sat) |
| `getDayOfYear()` | int | Day number in year |

### Date Arithmetic Methods (Immutable)

**All methods return NEW Jalalian instances**:

```php
// Year operations
public function addYears(int $years = 1): Jalalian
public function subYears(int $years = 1): Jalalian

// Month operations
public function addMonths(int $months = 1): Jalalian
public function subMonths(int $months = 1): Jalalian

// Day operations
public function addDays(int $days = 1): Jalalian
public function subDays(int $days = 1): Jalalian
public function addDay(): Jalalian
public function subDay(): Jalalian

// Time operations
public function addHours(int $hours = 1): Jalalian
public function subHours(int $hours = 1): Jalalian
public function addMinutes(int $minutes = 1): Jalalian
public function subMinutes(int $minutes = 1): Jalalian
public function addSeconds(int $secs = 1): Jalalian
public function subSeconds(int $secs = 1): Jalalian
```

**Chaining Example**:
```php
$future = Jalalian::now()
    ->addYears(1)
    ->addMonths(2)
    ->addDays(15);
```

### Comparison Methods

**Compare with Jalalian**:
```php
public function equalsTo(Jalalian $other): bool
public function greaterThan(Jalalian $other): bool
public function lessThan(Jalalian $other): bool
public function greaterThanOrEqualsTo(Jalalian $other): bool
public function lessThanOrEqualsTo(Jalalian $other): bool
```

**Compare with Carbon**:
```php
public function equalsToCarbon(Carbon $carbon): bool
public function greaterThanCarbon(Carbon $carbon): bool
public function lessThanCarbon(Carbon $carbon): bool
public function greaterThanOrEqualsToCarbon(Carbon $carbon): bool
public function lessThanOrEqualsToCarbon(Carbon $carbon): bool
```

### Temporal Check Methods

```php
public function isToday(): bool
public function isTomorrow(): bool
public function isYesterday(): bool
public function isFuture(): bool
public function isPast(): bool
public function isLeapYear(): bool
```

### Day of Week Methods

```php
public function isSaturday(): bool      // Week start (Persian)
public function isSunday(): bool
public function isMonday(): bool
public function isTuesday(): bool
public function isWednesday(): bool
public function isThursday(): bool
public function isFriday(): bool        // Weekend (Persian)
public function isDayOfWeek(int $day): bool
public function isStartOfWeek(): bool
public function isEndOfWeek(): bool
```

### Navigation Methods

```php
public function getFirstDayOfWeek(): Jalalian
public function getEndDayOfWeek(): Jalalian
public function getFirstDayOfMonth(): Jalalian
public function getEndDayOfMonth(): Jalalian
public function getEndDayOfYear(): Jalalian
public function getEndDayOfQuarter(): Jalalian
public function getNextWeek(): Jalalian
public function getNextMonth(): Jalalian
public function getLastWeek(): Jalalian
public function getLastMonth(): Jalalian
```

### Formatting and Output Methods

**`format(string $format): string`**

Formats date using PHP date() or strftime-style specifiers.

**PHP date() Format Characters**:
- **Y** - 4-digit year (1397)
- **y** - 2-digit year (97)
- **m** - Month with leading zero (01-12)
- **n** - Month without leading zero (1-12)
- **d** - Day with leading zero (01-31)
- **j** - Day without leading zero (1-31)
- **H** - 24-hour format
- **i** - Minutes
- **s** - Seconds
- **t** - Days in month
- **L** - Leap year (0 or 1)

**strftime-Style (Persian Output)**:
- **%Y** - 4-digit year
- **%m** - Month number
- **%d** - Day number
- **%B** - Full Persian month name (فروردین, مرداد, etc.)
- **%A** - Full Persian weekday name (شنبه, جمعه, etc.)
- **%H, %I, %M, %S** - Time components
- **%p** - Persian AM/PM (ق.ظ / ب.ظ)

**Examples**:
```php
echo $date->format('Y-m-d');          // 1397-05-24
echo $date->format('%B %d، %Y');      // مرداد 24، 1397
echo $date->format('%A, %d %B');      // جمعه، 24 مرداد
```

**Other Output Methods**:
```php
public function toString(): string           // Returns 'Y-m-d H:i:s'
public function __toString(): string         // Magic method alias
public function ago(): string                // Persian relative time
public function toArray(): array             // Complete date array
public function toCarbon(): Carbon           // Convert to Carbon
```

---

## CalendarUtils Class - Static Utilities

**Namespace**: `Morilog\Jalali\CalendarUtils`

### Conversion Methods

**`toGregorian(int $j_y, int $j_m, int $j_d): array`**

Converts Jalali to Gregorian date.

**Returns**: `[gregorian_year, gregorian_month, gregorian_day]`

**Example**:
```php
list($gy, $gm, $gd) = CalendarUtils::toGregorian(1395, 2, 18);
// Result: [2016, 5, 7]
```

**`toJalali(int $g_y, int $g_m, int $g_d): array`**

Converts Gregorian to Jalali date.

**Returns**: `[jalali_year, jalali_month, jalali_day]`

**Example**:
```php
list($jy, $jm, $jd) = CalendarUtils::toJalali(2016, 5, 7);
// Result: [1395, 2, 18]
```

### Validation Methods

**`checkDate(int $year, int $month, int $day, bool $isJalali = true): bool`**

Validates date correctness.

**Examples**:
```php
CalendarUtils::checkDate(1395, 12, 30, true);  // true (leap year)
CalendarUtils::checkDate(1396, 12, 30, true);  // false (not leap year)
CalendarUtils::checkDate(2016, 5, 7, false);   // true (Gregorian)
```

**`isLeapJalaliYear(int $year): bool`**

Checks if Jalali year is a leap year.

**Leap Year Pattern**: 33-year cycle, leap at positions 1, 5, 9, 13, 17, 22, 26, 30

### DateTime Creation Methods

**`createCarbonFromFormat(string $format, string $jalaliDate, ?DateTimeZone $timeZone = null): Carbon`**

Creates Carbon instance from Jalali formatted string.

**Example**:
```php
$carbon = CalendarUtils::createCarbonFromFormat('Y/m/d H:i:s', '1394/11/25 15:00:00');
```

**`createDatetimeFromFormat(string $format, string $jalaliDate): DateTime`**

Creates PHP DateTime from Jalali formatted string.

### String Formatting

**`strftime(string $format, int $timestamp): string`**

Formats Unix timestamp to Jalali string.

**Example**:
```php
$jalali = CalendarUtils::strftime('Y-m-d', strtotime('2016-05-08'));
// Result: 1395-02-19
```

### Number Conversion

**`convertNumbers(string $string, bool $toPersian = false): string`**

Converts between Latin (0-9) and Persian (۰-۹) numerals.

**Parameters**:
- `$toPersian`: false = Latin→Persian, true = Persian→Latin

**Examples**:
```php
// Latin to Persian
CalendarUtils::convertNumbers('1395-02-19');  // ۱۳۹۵-۰۲-۱۹

// Persian to Latin
CalendarUtils::convertNumbers('۱۳۹۵-۰۲-۱۹', true);  // 1395-02-19
```

---

## Complete API Reference: fisharebest/ext-calendar

### Architecture

**Six Calendar Classes** implementing `CalendarInterface`:
1. **GregorianCalendar** - Modern Western calendar
2. **JulianCalendar** - Pre-Gregorian calendar
3. **JewishCalendar** - Hebrew lunisolar calendar
4. **ArabicCalendar** - Islamic Hijri calendar
5. **PersianCalendar** - Jalali/Shamsi solar calendar
6. **FrenchCalendar** - French Republican calendar

### CalendarInterface Methods

**All calendar classes implement**:

```php
// Date conversion
public function ymdToJd(int $year, int $month, int $day): int
public function jdToYmd(int $julian_day): array

// Calendar properties
public function isLeapYear(int $year): bool
public function daysInMonth(int $year, int $month): int
public function monthsInYear(?int $year = null): int
public function daysInWeek(): int

// Range information
public function jdStart(): int
public function jdEnd(): int

// GEDCOM support
public function gedcomCalendarEscape(): string
```

### Persian Calendar Usage

```php
use Fisharebest\ExtCalendar\PersianCalendar;

$persian = new PersianCalendar();

// Convert date to Julian Day Number
$jd = $persian->ymdToJd(1400, 1, 1);  // Nowruz 1400

// Convert JDN back to date
list($year, $month, $day) = $persian->jdToYmd($jd);

// Check leap year
$isLeap = $persian->isLeapYear(1399);  // true

// Get days in month
$days = $persian->daysInMonth(1399, 12);  // 30 (leap year)

// Calendar properties
$months = $persian->monthsInYear();  // 12
$weekDays = $persian->daysInWeek();  // 7
```

### PHP ext/calendar Compatibility Functions

The package automatically provides these global functions:

**Date Conversions**:
```php
GregorianToJD(int $month, int $day, int $year): int
JDToGregorian(int $julianday): string
cal_to_jd(int $calendar, int $month, int $day, int $year): int
cal_from_jd(int $jd, int $calendar): array
```

**Calendar Information**:
```php
cal_info(int $calendar = -1): array
cal_days_in_month(int $calendar, int $month, int $year): int
JDDayOfWeek(int $julianday, int $mode = 0): mixed
JDMonthName(int $julianday, int $mode): string
```

**Usage Example**:
```php
// Works whether ext/calendar is installed or not
$jd = GregorianToJD(10, 7, 2023);
$days = cal_days_in_month(CAL_GREGORIAN, 2, 2024);  // 29
$info = cal_info(CAL_GREGORIAN);
```

---

## Laravel Integration Patterns

### Model Integration with Accessors/Mutators

**Best Practice: Store Gregorian, Display Jalali**

```php
use Morilog\Jalali\Jalalian;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $dates = ['publish_date'];
    
    // Accessor: Convert Gregorian to Jalali for display
    public function getPublishDateJalaliAttribute()
    {
        return $this->publish_date 
            ? Jalalian::fromCarbon($this->publish_date)->format('Y/m/d')
            : null;
    }
    
    // Mutator: Convert Jalali to Gregorian for storage
    public function setPublishDateJalaliAttribute($value)
    {
        $this->attributes['publish_date'] = 
            Jalalian::fromFormat('Y/m/d', $value)->toCarbon();
    }
    
    // Formatted accessor for Persian display
    public function getPublishDateFormattedAttribute()
    {
        return $this->publish_date
            ? Jalalian::fromCarbon($this->publish_date)->format('%A، %d %B %Y')
            : null;
    }
}

// Usage
$post->publish_date_jalali = '1399/08/06';  // Stores as 2020-10-27
echo $post->publish_date_jalali;             // Displays 1399/08/06
echo $post->publish_date_formatted;          // پنج‌شنبه، 06 مرداد 1399
```

### Carbon Macro Extension

**Extend Carbon with Jalali methods**:

```php
// In AppServiceProvider::boot()
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;

Carbon::macro('toJalali', function($format = 'Y/m/d H:i:s') {
    return Jalalian::fromCarbon($this)->format($format);
});

Carbon::macro('toJalaliFormatted', function() {
    return Jalalian::fromCarbon($this)->format('%A، %d %B %Y');
});

// Usage throughout application
$date = $model->created_at->toJalali('Y/m/d');  // 1399/08/06
$formatted = Carbon::now()->toJalaliFormatted(); // جمعه، 15 مهر 1402
```

### Custom Validation Rule

```php
use Illuminate\Support\Facades\Validator;
use Morilog\Jalali\CalendarUtils;

// Register in AppServiceProvider::boot()
Validator::extend('jalali_date', function($attribute, $value, $parameters, $validator) {
    // Check format
    if (!preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $value)) {
        return false;
    }
    
    // Validate date components
    [$year, $month, $day] = explode('/', $value);
    return CalendarUtils::checkDate((int)$year, (int)$month, (int)$day, true);
});

// Custom error message
Validator::replacer('jalali_date', function($message, $attribute, $rule, $parameters) {
    return 'تاریخ :attribute معتبر نیست';
});

// Usage in Form Request
public function rules()
{
    return [
        'birth_date' => 'required|jalali_date',
        'start_date' => 'required|jalali_date',
    ];
}
```

### Blade Directives

```php
// In AppServiceProvider::boot()
use Illuminate\Support\Facades\Blade;
use Morilog\Jalali\Jalalian;

Blade::directive('jalali', function ($expression) {
    return "<?php echo Jalalian::fromCarbon($expression)->format('Y/m/d'); ?>";
});

Blade::directive('jalaliFormatted', function ($expression) {
    return "<?php echo Jalalian::fromCarbon($expression)->format('%A، %d %B %Y'); ?>";
});
```

**Blade Template Usage**:
```blade
<article>
    <h1>{{ $post->title }}</h1>
    <p>تاریخ انتشار: @jalali($post->publish_date)</p>
    <p>بروزرسانی: @jalaliFormatted($post->updated_at)</p>
</article>
```

### API Resource Formatting

```php
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            
            // Dual calendar support
            'dates' => [
                'publish_date' => $this->publish_date->toIso8601String(),
                'publish_date_jalali' => Jalalian::fromCarbon($this->publish_date)
                    ->format('Y/m/d'),
                'publish_date_formatted' => Jalalian::fromCarbon($this->publish_date)
                    ->format('%A، %d %B %Y'),
            ],
            
            'created_at' => $this->created_at->toIso8601String(),
            'created_at_jalali' => $this->created_at->toJalali('Y/m/d H:i:s'),
        ];
    }
}
```

### Controller Implementation

```php
use Morilog\Jalali\Jalalian;
use Morilog\Jalali\CalendarUtils;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'publish_date' => 'required|jalali_date',
        ]);
        
        // Convert Jalali to Carbon for storage
        $post = Post::create([
            'title' => $validated['title'],
            'publish_date' => Jalalian::fromFormat('Y/m/d', $validated['publish_date'])
                ->toCarbon(),
        ]);
        
        return new PostResource($post);
    }
    
    public function filterByDateRange(Request $request)
    {
        $startJalali = $request->input('start_date');  // 1399/01/01
        $endJalali = $request->input('end_date');      // 1399/12/29
        
        // Convert to Carbon for database query
        $startCarbon = Jalalian::fromFormat('Y/m/d', $startJalali)->toCarbon();
        $endCarbon = Jalalian::fromFormat('Y/m/d', $endJalali)->toCarbon();
        
        $posts = Post::whereBetween('publish_date', [$startCarbon, $endCarbon])
            ->get();
        
        return PostResource::collection($posts);
    }
}
```

### Database Storage Strategy

**CRITICAL: Always store as Gregorian DATE/DATETIME**

```php
// Migration
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->date('publish_date');        // Store as Gregorian
    $table->timestamp('scheduled_at')->nullable();
    $table->timestamps();
    
    // Add indexes for date queries
    $table->index('publish_date');
});

// Never do this:
// $table->string('publish_date_jalali');  // ❌ WRONG

// Store Gregorian, use accessors for Jalali display
```

### Query Scopes for Jalali Date Filtering

```php
class Post extends Model
{
    // Scope for filtering by Jalali date range
    public function scopePublishedBetweenJalali($query, $startJalali, $endJalali)
    {
        $start = Jalalian::fromFormat('Y/m/d', $startJalali)->toCarbon();
        $end = Jalalian::fromFormat('Y/m/d', $endJalali)->toCarbon();
        
        return $query->whereBetween('publish_date', [$start, $end]);
    }
    
    // Scope for current Jalali year
    public function scopeCurrentJalaliYear($query)
    {
        $currentYear = Jalalian::now()->getYear();
        
        $yearStart = new Jalalian($currentYear, 1, 1);
        $yearEnd = new Jalalian($currentYear, 12, 29);
        
        return $query->whereBetween('publish_date', [
            $yearStart->toCarbon(),
            $yearEnd->toCarbon()
        ]);
    }
}

// Usage
$posts = Post::publishedBetweenJalali('1399/01/01', '1399/12/29')->get();
$thisYear = Post::currentJalaliYear()->get();
```

### Timezone Configuration

**Persian calendar year starts at vernal equinox in Iran Standard Time (UTC+3:30)**

```php
// config/app.php
'timezone' => 'Asia/Tehran',  // ✅ CORRECT
// NOT 'Asia/Iran' (invalid timezone) ❌

// Explicit timezone in code
$tehranTz = new DateTimeZone('Asia/Tehran');
$date = Jalalian::now($tehranTz);

// Carbon with timezone
$carbon = Carbon::now('Asia/Tehran');
$jalali = Jalalian::fromCarbon($carbon);
```

---

## Advanced Features

### Complete Format Specifier Reference

#### PHP date() Style Formats

| Specifier | Output | Description |
|-----------|--------|-------------|
| Y | 1397 | 4-digit year |
| y | 97 | 2-digit year |
| m | 01-12 | Month with leading zero |
| n | 1-12 | Month without leading zero |
| d | 01-31 | Day with leading zero |
| j | 1-31 | Day without leading zero |
| H | 00-23 | 24-hour with leading zero |
| G | 0-23 | 24-hour without leading zero |
| i | 00-59 | Minutes with leading zero |
| s | 00-59 | Seconds with leading zero |
| t | 29-31 | Days in current month |
| L | 0/1 | Leap year (1=yes, 0=no) |

#### strftime-Style Formats (Persian Output)

| Specifier | Example Output | Description |
|-----------|----------------|-------------|
| %Y | 1397 | 4-digit year |
| %y | 97 | 2-digit year |
| %m | 08 | Month number with zero |
| %d | 06 | Day with leading zero |
| %B | مرداد | Full Persian month name |
| %A | جمعه | Full Persian weekday name |
| %H | 14 | Hour (24-hour) |
| %I | 02 | Hour (12-hour) |
| %M | 30 | Minute |
| %S | 45 | Second |
| %p | ب.ظ | Persian AM/PM |

**Practical Examples**:
```php
$date = Jalalian::fromFormat('Y/m/d', '1399/08/06');

echo $date->format('Y-m-d');              // 1399-08-06
echo $date->format('Y/m/d H:i:s');        // 1399/08/06 00:00:00
echo $date->format('%B %d، %Y');          // مرداد 06، 1399
echo $date->format('%A، %d %B %Y');       // پنج‌شنبه، 06 مرداد 1399
```

### Persian Calendar Structure

#### Month Names and Characteristics

| # | Persian | Transliteration | Season | Days | Zodiac |
|---|---------|----------------|--------|------|--------|
| 1 | فروردین | Farvardin | Spring | 31 | Aries |
| 2 | اردیبهشت | Ordibehesht | Spring | 31 | Taurus |
| 3 | خرداد | Khordad | Spring | 31 | Gemini |
| 4 | تیر | Tir | Summer | 31 | Cancer |
| 5 | مرداد | Mordad | Summer | 31 | Leo |
| 6 | شهریور | Shahrivar | Summer | 31 | Virgo |
| 7 | مهر | Mehr | Autumn | 30 | Libra |
| 8 | آبان | Aban | Autumn | 30 | Scorpio |
| 9 | آذر | Azar | Autumn | 30 | Sagittarius |
| 10 | دی | Dey | Winter | 30 | Capricorn |
| 11 | بهمن | Bahman | Winter | 30 | Aquarius |
| 12 | اسفند | Esfand | Winter | 29/30* | Pisces |

*29 days in common years, 30 in leap years

#### Weekday Names

| # | Persian | Transliteration | Note |
|---|---------|----------------|------|
| 0 | شنبه | Shanbe | Saturday (week start) |
| 1 | یکشنبه | Yekshanbe | Sunday |
| 2 | دوشنبه | Doshanbe | Monday |
| 3 | سه‌شنبه | Seshanbe | Tuesday |
| 4 | چهارشنبه | Chaharshanbe | Wednesday |
| 5 | پنج‌شنبه | Panjshanbe | Thursday |
| 6 | جمعه | Jom'e | Friday (weekend) |

### Numeral Conversion

**Bidirectional Persian/Latin conversion**:

```php
use Morilog\Jalali\CalendarUtils;

// Latin → Persian
$persianDate = CalendarUtils::convertNumbers('1402/07/15');
// Result: ۱۴۰۲/۰۷/۱۵

// Persian → Latin
$latinDate = CalendarUtils::convertNumbers('۱۴۰۲/۰۷/۱۵', true);
// Result: 1402/07/15

// In user input processing
$userInput = request('date');  // May contain Persian numerals
$normalized = CalendarUtils::convertNumbers($userInput, true);
$carbon = Jalalian::fromFormat('Y/m/d', $normalized)->toCarbon();
```

### Performance Optimization

#### Algorithm Performance

**Borkowski Algorithm Characteristics**:
- **Complexity**: O(1) - constant time
- **Valid Range**: 1799-2256 CE (457 years)
- **Accuracy**: ±1 day maximum deviation
- **Method**: Astronomical vernal equinox approximation
- **Precision**: ±2″ in solar coordinates

#### Optimization Strategies

**1. Cache Repeated Conversions**:
```php
use Illuminate\Support\Facades\Cache;

function getCachedJalaliDate($carbon) {
    $cacheKey = 'jalali_' . $carbon->format('Y-m-d');
    
    return Cache::remember($cacheKey, 3600, function() use ($carbon) {
        return Jalalian::fromCarbon($carbon)->format('Y/m/d');
    });
}
```

**2. Minimize Object Creation**:
```php
// ❌ Bad: Multiple conversions
foreach ($posts as $post) {
    echo Jalalian::fromCarbon($post->created_at)->format('Y/m/d');
    echo Jalalian::fromCarbon($post->created_at)->format('%B %Y');
}

// ✅ Good: Single conversion
foreach ($posts as $post) {
    $jalali = Jalalian::fromCarbon($post->created_at);
    echo $jalali->format('Y/m/d');
    echo $jalali->format('%B %Y');
}
```

**3. Batch Operations**:
```php
// Efficient collection mapping
$jalaliDates = collect($posts)->map(fn($post) => 
    Jalalian::fromCarbon($post->created_at)
);
```

**4. Eager Loading with Accessors**:
```php
// Load relationships efficiently
$posts = Post::with('author')
    ->get()
    ->map(function($post) {
        $post->jalali_date = $post->publish_date_jalali;
        return $post;
    });
```

### Thread Safety and Immutability

#### morilog/jalali v3.x - Fully Immutable

```php
$date1 = Jalalian::now();
$date2 = $date1->addMonths(1);  // Returns NEW instance

// Original unchanged
echo $date1->format('Y/m/d');  // 1402/07/15
echo $date2->format('Y/m/d');  // 1402/08/15

// Thread-safe for concurrent requests
Route::get('/report', function() {
    $date = Jalalian::now();  // New instance per request
    return $date->format('Y/m/d');
});
```

**Benefits**:
- ✅ Safe for concurrent requests
- ✅ No shared mutable state
- ✅ Can use in Laravel queues/jobs
- ✅ Predictable behavior

#### fisharebest/ext-calendar - Stateless

```php
$calendar = new PersianCalendar();

// Pure functions - no state modification
$jd1 = $calendar->ymdToJd(1400, 6, 15);
$jd2 = $calendar->ymdToJd(1400, 6, 16);  // Independent

// Thread-safe by design
```

### Leap Year Handling

**33-Year Cycle Pattern**:

Leap years occur when `year % 33` equals: **1, 5, 9, 13, 17, 22, 26, 30**

```php
// Check leap years
CalendarUtils::isLeapJalaliYear(1395);  // true
CalendarUtils::isLeapJalaliYear(1399);  // true
CalendarUtils::isLeapJalaliYear(1400);  // false

// Validate Esfand (month 12) dates
CalendarUtils::checkDate(1395, 12, 30, true);  // true (leap)
CalendarUtils::checkDate(1396, 12, 30, true);  // false (common)
```

**Leap Year Table (Recent Years)**:

| Year | Leap? | Esfand Days |
|------|-------|-------------|
| 1395 | ✅ Yes | 30 |
| 1396 | ❌ No | 29 |
| 1397 | ❌ No | 29 |
| 1398 | ❌ No | 29 |
| 1399 | ✅ Yes | 30 |
| 1400 | ❌ No | 29 |
| 1403 | ✅ Yes | 30 |

### Date Range Limitations

**morilog/jalali (Borkowski Algorithm)**:
- ✅ **Reliable**: 1799-2256 CE (1178-1634 AP)
- ⚠️ **Degraded**: Beyond 2256 CE
- ❌ **Not Recommended**: Before 1799 CE

**fisharebest/ext-calendar**:
- ✅ Broader range via Julian Day Numbers
- ✅ Suitable for historical dates

### Timezone Critical Considerations

**Nowruz (New Year) Edge Case**:

The Persian year starts at the exact moment of vernal equinox in Tehran timezone. Dates near March 20-21 require careful timezone handling:

```php
$tehranTz = new DateTimeZone('Asia/Tehran');

// Critical date near equinox
$criticalMoment = Carbon::parse('2021-03-20 12:30:00', $tehranTz);
$jalali = Jalalian::fromDateTime($criticalMoment, $tehranTz);

// Could be 1399/12/30 or 1400/01/01 depending on exact equinox time
```

**Best Practice**: Always use `Asia/Tehran` timezone for Persian calendar operations:
```php
// ✅ Correct
'timezone' => 'Asia/Tehran'

// ❌ Wrong
'timezone' => 'Asia/Iran'  // Does not exist
'timezone' => 'UTC'        // Will cause 3:30 hour offset
```

---

## Common Pitfalls and Solutions

### Issue 1: Service Provider Error (v2→v3 Migration)

**Error**: `Class 'Morilog\Jalali\JalaliServiceProvider' not found`

**Cause**: Version 3.x removed service provider

**Solution**:
```php
// Remove from config/app.php:
'providers' => [
    // Morilog\Jalali\JalaliServiceProvider::class,  // DELETE
],

// Use classes directly:
use Morilog\Jalali\Jalalian;
$date = Jalalian::now();
```

### Issue 2: PHP 8.1 Performance Problem

**Symptoms**: Very slow execution, float conversion warnings

**Solution**: Update to v3.3.0+
```bash
composer require morilog/jalali:^3.3
```

### Issue 3: Database Storage Error

**Error**: `Incorrect date value: '1399/06/31'`

**Cause**: Storing Jalali date directly in DATE column

**Solution**:
```php
// ❌ Wrong
$model->date = '1399/06/31';

// ✅ Correct
$jalaliInput = '1399/06/31';
$model->date = Jalalian::fromFormat('Y/m/d', $jalaliInput)->toCarbon();
```

### Issue 4: Timezone Mismatch

**Symptoms**: 3:30 hour difference in dates/times

**Solution**: Configure correct timezone
```php
// config/app.php
'timezone' => 'Asia/Tehran',  // NOT 'Asia/Iran'
```

### Issue 5: Persian Numeral Input

**Problem**: Cannot parse '۱۳۹۹/۰۸/۰۶'

**Solution**:
```php
$userInput = '۱۳۹۹/۰۸/۰۶';
$latin = CalendarUtils::convertNumbers($userInput, true);  // '1399/08/06'
$carbon = Jalalian::fromFormat('Y/m/d', $latin)->toCarbon();
```

### Issue 6: Format String Problems

**Problem**: Predefined formats don't work in v3.x

**Solution**:
```php
// ⚠️ Problematic
$date->format('datetime');
$date->format('date');

// ✅ Use explicit formats
$date->format('Y-m-d H:i:s');
$date->format('Y/m/d');
```

---

## Version Compatibility Matrix

### morilog/jalali

| Version | PHP | Laravel 6 | Laravel 7 | Laravel 8 | Laravel 9 | Laravel 10 | Laravel 11 | Carbon | Status |
|---------|-----|-----------|-----------|-----------|-----------|------------|------------|--------|---------|
| 3.4.2 | 7.0-8.x | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | 1.21-3.0 | Current |
| 3.3.0 | 7.0-8.x | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ | 1.21-2.0 | Stable |
| 3.0.9 | ≥7.0 | ✅ | ✅ | ✅ | ⚠️ | ❌ | ❌ | 1.21-2.0 | Legacy |
| 2.3.0 | ≥5.6 | ✅ | ✅ | ⚠️ | ❌ | ❌ | ❌ | 1.21-2.0 | Deprecated |

### fisharebest/ext-calendar

| Version | PHP | Status |
|---------|-----|---------|
| 2.6.0 | ≥5.3.0 | Current |
| 2.5.0 | ≥5.3.0 | Stable |

---

## Practical Implementation Examples

### Complete Laravel Application Example

**1. Migration**:
```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->date('publish_date');
    $table->timestamps();
    
    $table->index('publish_date');
});
```

**2. Model**:
```php
use Morilog\Jalali\Jalalian;

class Post extends Model
{
    protected $fillable = ['title', 'content', 'publish_date'];
    protected $dates = ['publish_date'];
    
    public function getPublishDateJalaliAttribute()
    {
        return Jalalian::fromCarbon($this->publish_date)->format('Y/m/d');
    }
    
    public function setPublishDateJalaliAttribute($value)
    {
        $this->attributes['publish_date'] = 
            Jalalian::fromFormat('Y/m/d', $value)->toCarbon();
    }
    
    public function scopePublishedInJalaliYear($query, $year)
    {
        $start = new Jalalian($year, 1, 1);
        $end = new Jalalian($year, 12, 29);
        
        return $query->whereBetween('publish_date', [
            $start->toCarbon(),
            $end->toCarbon()
        ]);
    }
}
```

**3. Controller**:
```php
class PostController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'publish_date' => 'required|jalali_date',
        ]);
        
        $post = Post::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'publish_date' => Jalalian::fromFormat('Y/m/d', $validated['publish_date'])
                ->toCarbon(),
        ]);
        
        return response()->json([
            'message' => 'پست با موفقیت ایجاد شد',
            'post' => new PostResource($post),
        ], 201);
    }
    
    public function index(Request $request)
    {
        $query = Post::query();
        
        if ($request->has(['start_date', 'end_date'])) {
            $start = Jalalian::fromFormat('Y/m/d', $request->start_date)->toCarbon();
            $end = Jalalian::fromFormat('Y/m/d', $request->end_date)->toCarbon();
            $query->whereBetween('publish_date', [$start, $end]);
        }
        
        return PostResource::collection($query->paginate());
    }
}
```

**4. API Resource**:
```php
class PostResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'publish_date' => [
                'gregorian' => $this->publish_date->toDateString(),
                'jalali' => $this->publish_date_jalali,
                'formatted' => Jalalian::fromCarbon($this->publish_date)
                    ->format('%A، %d %B %Y'),
            ],
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

**5. Blade View**:
```blade
@foreach($posts as $post)
    <article>
        <h2>{{ $post->title }}</h2>
        <time>@jalali($post->publish_date)</time>
        <div>{{ $post->content }}</div>
    </article>
@endforeach
```

### Real-World Date Range Filtering

```php
// Filter posts by Jalali month
public function getPostsByMonth($year, $month)
{
    $monthStart = new Jalalian($year, $month, 1);
    $monthEnd = new Jalalian($year, $month, $monthStart->getMonthDays());
    
    return Post::whereBetween('publish_date', [
        $monthStart->toCarbon(),
        $monthEnd->toCarbon()
    ])->get();
}

// Get current Jalali year posts
public function getCurrentYearPosts()
{
    $currentYear = Jalalian::now()->getYear();
    
    return Post::publishedInJalaliYear($currentYear)->get();
}
```

### Date Conversion Utility Service

```php
namespace App\Services;

use Morilog\Jalali\Jalalian;
use Morilog\Jalali\CalendarUtils;
use Carbon\Carbon;

class JalaliDateService
{
    public function parseUserInput(string $input): Carbon
    {
        // Handle Persian numerals
        $normalized = CalendarUtils::convertNumbers($input, true);
        
        // Validate
        if (!$this->validate($normalized)) {
            throw new \InvalidArgumentException('Invalid Jalali date');
        }
        
        return Jalalian::fromFormat('Y/m/d', $normalized)->toCarbon();
    }
    
    public function format(Carbon $carbon, string $format = 'Y/m/d'): string
    {
        return Jalalian::fromCarbon($carbon)->format($format);
    }
    
    public function formatPersian(Carbon $carbon): string
    {
        $jalali = Jalalian::fromCarbon($carbon);
        $formatted = $jalali->format('%A، %d %B %Y');
        return CalendarUtils::convertNumbers($formatted);
    }
    
    public function validate(string $date): bool
    {
        if (!preg_match('/^\d{4}\/\d{1,2}\/\d{1,2}$/', $date)) {
            return false;
        }
        
        [$year, $month, $day] = explode('/', $date);
        return CalendarUtils::checkDate((int)$year, (int)$month, (int)$day, true);
    }
}
```

---

## Testing Strategies

### Unit Tests

```php
namespace Tests\Unit;

use Tests\TestCase;
use Morilog\Jalali\Jalalian;
use Morilog\Jalali\CalendarUtils;

class JalaliDateTest extends TestCase
{
    /** @test */
    public function it_converts_gregorian_to_jalali()
    {
        $result = CalendarUtils::toJalali(2016, 5, 7);
        $this->assertEquals([1395, 2, 18], $result);
    }
    
    /** @test */
    public function it_validates_leap_years()
    {
        $this->assertTrue(CalendarUtils::isLeapJalaliYear(1395));
        $this->assertTrue(CalendarUtils::checkDate(1395, 12, 30, true));
        $this->assertFalse(CalendarUtils::checkDate(1396, 12, 30, true));
    }
    
    /** @test */
    public function it_handles_immutability()
    {
        $original = Jalalian::now();
        $modified = $original->addMonths(1);
        
        $this->assertNotSame($original, $modified);
    }
    
    /** @test */
    public function it_converts_persian_numerals()
    {
        $persian = CalendarUtils::convertNumbers('1395-02-19');
        $this->assertEquals('۱۳۹۵-۰۲-۱۹', $persian);
        
        $latin = CalendarUtils::convertNumbers($persian, true);
        $this->assertEquals('1395-02-19', $latin);
    }
}
```

### Feature Tests

```php
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostDateHandlingTest extends TestCase
{
    use RefreshDatabase;
    
    /** @test */
    public function it_stores_jalali_dates_correctly()
    {
        $response = $this->postJson('/api/posts', [
            'title' => 'Test',
            'publish_date' => '1399/08/06'
        ]);
        
        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'publish_date' => '2020-10-27'
        ]);
    }
    
    /** @test */
    public function it_filters_by_jalali_date_range()
    {
        Post::factory()->create(['publish_date' => '2020-05-01']);
        Post::factory()->create(['publish_date' => '2020-08-01']);
        Post::factory()->create(['publish_date' => '2020-11-01']);
        
        $response = $this->getJson('/api/posts?start_date=1399/05/01&end_date=1399/08/30');
        
        $response->assertStatus(200)
                 ->assertJsonCount(2, 'data');
    }
}
```

---

## Best Practices Summary

### Database Design
- ✅ **Always store Gregorian dates** in DATE/DATETIME columns
- ✅ **Convert to Jalali only for display** using accessors
- ✅ **Index date columns** for efficient queries
- ❌ **Never store Jalali strings** in database

### Performance
- ✅ **Cache frequent conversions**
- ✅ **Minimize object creation** in loops
- ✅ **Use batch operations** for collections
- ✅ **Eager load relationships** when needed

### Validation
- ✅ **Create custom validation rules** for Jalali dates
- ✅ **Handle Persian numerals** in user input
- ✅ **Validate before conversion** to avoid exceptions
- ✅ **Check leap years** for month 12 validation

### Timezone
- ✅ **Always use 'Asia/Tehran'** timezone
- ✅ **Be aware of Nowruz edge cases** (March 20-21)
- ✅ **Configure timezone consistently** across application
- ❌ **Never use 'Asia/Iran'** (invalid)

### Code Organization
- ✅ **Use accessors/mutators** in models
- ✅ **Create service classes** for complex logic
- ✅ **Extend Carbon with macros** for convenience
- ✅ **Use Blade directives** for views

---

## Calendar System Comparison

### Jalali Calendar Characteristics

**Accuracy**: One of the world's most accurate solar calendars
- Error: 1 day in 3.8 million years (theoretical)
- Gregorian error: 1 day in 3,030 years
- Julian error: 1 day in 128 years

**Structure**:
- **Year start**: Vernal equinox (astronomically determined)
- **Month structure**: First 6 months = 31 days, next 5 = 30 days, last = 29/30
- **Week**: 7 days (Saturday to Friday)
- **Leap cycle**: 33-year pattern with 8 leap years

**Cultural Context**:
- Official calendar in Iran and Afghanistan
- Used for 900+ years since Omar Khayyam (1079 CE)
- Integrates Zoroastrian traditions with Islamic epoch
- Nowruz (New Year) is major cultural celebration

### Use Case Recommendations

**Use morilog/jalali when**:
- Building Laravel web applications
- Need rich formatting options
- Require Persian localization
- Want Carbon integration
- User-facing date display needed

**Use fisharebest/ext-calendar when**:
- Need multiple calendar systems
- Working without ext/calendar extension
- Performing calendar calculations only
- Framework-agnostic requirements
- Genealogy or historical date work

---

## Conclusion

Both **morilog/jalali** and **fisharebest/ext-calendar** provide production-ready Persian calendar support for PHP applications. morilog/jalali excels in Laravel integration with its immutable design, extensive formatting, and Carbon interoperability, making it ideal for modern web applications with Persian users. fisharebest/ext-calendar offers a lightweight, multi-calendar solution suitable for broader calendar conversion needs.

**Key Takeaways**:

- **Always store Gregorian** dates in databases, convert to Jalali for display only
- **Version 3.x** of morilog/jalali is fully immutable and thread-safe
- **Borkowski algorithm** provides high accuracy within 1799-2256 CE range
- **Timezone configuration** is critical - always use 'Asia/Tehran'
- **Format specifiers** support both PHP date() and strftime-style with Persian output
- **Leap year handling** follows 33-year cycle with specific validation requirements
- **Performance optimization** through caching and minimizing object creation
- **Laravel integration** via accessors, mutators, validation rules, and Blade directives

This documentation provides everything needed to implement robust Jalali calendar functionality in Laravel applications, from basic API usage to advanced optimization strategies and production deployment patterns.