# Multi-Calendar Conversion System Implementation Plan

## 📋 Overview

This plan implements a comprehensive calendar conversion system for EpochBridge, extending beyond the existing Jalali calendar support to include **14 additional calendar systems**. 

**🎉 STATUS: IMPLEMENTATION COMPLETE** - All calendar systems have been successfully implemented and integrated into the application as of October 2025.

**📋 ARCHITECTURAL UPDATE: SUCCESSFUL DEVIATION** - The implementation successfully deviates from the original plan with a superior unified Livewire component architecture that provides better UX and maintainability than the originally planned individual components and REST API approach.

### 🔍 Existing Support Analysis
- ✅ `fisharebest/ext-calendar` already supports: Hebrew, Islamic/Hijri, French Republican
- ✅ `morilog/jalali` provides Persian/Jalali calendar
- ✅ Julian Day conversions are supported through existing infrastructure

### 🎯 Target Calendar Systems - ✅ ALL IMPLEMENTED
| Calendar System | Implementation Method | Status | Service Class |
|---|---|---|---|
| Julian Day/Modified Julian Day | Existing package | ✅ Complete | `JulianDayService.php` |
| Hebrew Calendar | Existing package | ✅ Complete | `HebrewCalendarService.php` |
| Islamic/Hijri Calendar | Existing package | ✅ Complete | `IslamicCalendarService.php` |
| French Republican | Existing package | ✅ Complete | `FrenchRepublicanService.php` |
| Chinese Lunisolar | Custom implementation | ✅ Complete | `ChineseCalendarService.php` |
| Japanese Era-based | Custom implementation | ✅ Complete | `JapaneseCalendarService.php` |
| Babylonian | Custom implementation | ✅ Complete | `BabylonianCalendarService.php` |
| Assyrian | Custom implementation | ✅ Complete | `AssyrianCalendarService.php` |
| Ancient Macedonian | Custom implementation | ✅ Complete | `AncientMacedonianService.php` |
| Coptic | Custom implementation | ✅ Complete | `CopticCalendarService.php` |
| Egyptian | Custom implementation | ✅ Complete | `EgyptianCalendarService.php` |
| Ethiopian | Custom implementation | ✅ Complete | `EthiopianCalendarService.php` |
| Mayan | Custom implementation | ✅ Complete | `MayanCalendarService.php` |

## 🚀 Phase Structure

## 📁 Phase 1: Refactor Jalali Support & Implement Core Calendars

> **🎯 GOAL:** Refactor existing Jalali support into a new extensible architecture and implement Julian Day conversions, while leveraging existing package support for Hebrew, Islamic, and French Republican calendars

> **📖 CONTEXT:** The application's core Jalali conversion feature resides in `app/Services/DateTimeConverterService.php`. The `fisharebest/ext-calendar` package provides Julian Day Number (JD) support and other calendar systems. This phase will refactor the existing Jalali logic into a new, extensible architecture and expose the other pre-supported calendars through services and user interfaces.

> **📋 TASK:** Create conversion services for Julian Day formats and integrate existing calendar support

### ✅ Requirements
- [x] Jalali Calendar integration and refactoring
- [x] Julian Day Number (JD) conversion
- [x] Modified Julian Day (MJD) conversion  
- [x] Julian Date-time conversion
- [x] Hebrew Calendar integration
- [x] Islamic/Hijri Calendar integration
- [x] French Republican Calendar integration
- [x] RESTful API endpoints
- [x] Livewire components for frontend

### 📐 Guidelines
- ✅ Use existing `morilog/jalali` and `fisharebest/ext-calendar` infrastructure
- ✅ Create Laravel service classes following SOLID principles
- ✅ Implement standardized error handling for common exceptions (e.g., invalid dates).
- ✅ Enforce specific timezones where required for accuracy (e.g., `Asia/Tehran` for Jalali).
- ✅ Follow TALL Stack patterns for frontend

### 🔧 Implementation Details

#### 🖥️ Backend Changes

1. **📦 Create Base Calendar Service** - `app/Services/CalendarConversionService.php`
   - Abstract base class for all calendar conversions.
   - Defines a standardized approach for date validation, timezone management, and common exception handling.
   - Provides Julian Day Number utilities.

2. **🇮🇷 Create Jalali Calendar Service** - `app/Services/JalaliCalendarService.php`
   - Extends `CalendarConversionService`.
   - Refactors existing conversion logic from `DateTimeConverterService.php`.
   - Encapsulates usage of the `morilog/jalali` package.
   - **Crucially, enforces the `Asia/Tehran` timezone** for all calculations to ensure accuracy.

3. **📅 Create Julian Day Service** - `app/Services/JulianDayService.php`
   ```php
   public function convertToJulianDay(Carbon $date): int
   public function convertToModifiedJulianDay(Carbon $date): float
   public function convertToJulianDateTime(Carbon $date): string
   public function convertFromJulianDay(int $jd): Carbon
   ```

4. **🏛️ Create Calendar-Specific Services:**
   - 📜 `app/Services/HebrewCalendarService.php`
   - 🕌 `app/Services/IslamicCalendarService.php` 
   - 🇫🇷 `app/Services/FrenchRepublicanService.php`

4. **🌐 Create API Controller** - `app/Http/Controllers/Api/CalendarConversionController.php`
   - RESTful endpoints for each conversion type
   - Input validation using Form Requests
   - Proper error responses and HTTP status codes

5. **✅ Create Form Requests:**
   - `app/Http/Requests/DateConversionRequest.php`
   - Validate Gregorian input dates
   - Handle timezone considerations

6. **📋 Create Response Resources:**
   - `app/Http/Resources/CalendarConversionResource.php`
   - Standardized API response format

#### 🖼️ Frontend Changes

1. **🎛️ Create Main Conversion Component** - `resources/views/livewire/calendar-converter.blade.php`
   - Single-file Volt component
   - Alpine.js for client-side interactions
   - Real-time conversion display

2. **🔧 Create Specialized Components:**
   - 📅 `resources/views/livewire/julian-day-converter.blade.php`
   - 📜 `resources/views/livewire/hebrew-calendar-converter.blade.php`
   - 🕌 `resources/views/livewire/islamic-calendar-converter.blade.php`
   - 🇫🇷 `resources/views/livewire/french-republican-converter.blade.php`

3. **🧭 Add Navigation** - Update `resources/views/components/layouts/app/sidebar.blade.php`
   - Calendar conversion menu items
   - Icon integration with Flux UI

4. **📊 Create Conversion Results Component** - `resources/views/components/conversion-result.blade.php`
   - Reusable component for displaying conversion results
   - Copy-to-clipboard functionality using Alpine.js

#### 🧪 Testing
- ✅ Feature tests for each calendar service
- 🌐 API endpoint tests with various date inputs
- ⚡ Livewire component tests for user interactions
- 🎯 Edge case testing (leap years, invalid dates)

---

## 📁 Phase 2: East Asian Calendar Systems

> **🎯 GOAL:** Implement Chinese lunisolar and Japanese era-based calendar systems

> **📖 CONTEXT:** These calendars require complex astronomical calculations and cultural/historical era data that aren't available in existing packages.

> **📋 TASK:** Create custom implementations for Chinese and Japanese calendar systems

### ✅ Requirements
- [x] Chinese lunisolar calendar with proper leap month handling
- [x] Japanese calendar with era names (Reiwa, Heisei, etc.)
- [x] Astronomical calculations for Chinese calendar
- [x] Historical era data management for Japanese calendar
- [x] Timezone handling for regional accuracy

### 📐 Guidelines
- ✅ Implement astronomical algorithms for Chinese calendar
- ✅ Create era data management system for Japanese calendar
- ✅ Ensure cultural accuracy in naming conventions
- ✅ Handle historical transitions properly

### 🔧 Implementation Details

#### 🖥️ Backend Changes

1. **🐉 Create Chinese Calendar Service** - `app/Services/ChineseCalendarService.php`
   - Lunisolar calculations based on astronomical data
   - Leap month detection and handling
   - Chinese zodiac animal and element calculations
   ```php
   public function convertToChineseLunisolar(Carbon $date): array
   public function getChineseYear(Carbon $date): string
   public function getChineseZodiac(Carbon $date): array
   ```

2. **🇯🇵 Create Japanese Calendar Service** - `app/Services/JapaneseCalendarService.php`
   - Era name management (Reiwa 令和, Heisei 平成, etc.)
   - Imperial year calculations
   - Era transition handling
   ```php
   public function convertToJapaneseEra(Carbon $date): array
   public function getCurrentEra(): JapaneseEra
   public function getEraYear(Carbon $date): int
   ```

3. **📚 Create Era Data Model** - `app/Models/JapaneseEra.php`
   - Database storage for era information
   - Start/end dates, names (English/Japanese), romanization

4. **🗄️ Create Migration** - `database/migrations/create_japanese_eras_table.php`
   - Era data table structure
   - Seeder for historical and current eras

5. **🌙 Create Chinese Calendar Data Service** - `app/Services/ChineseAstronomicalService.php`
   - New moon calculations
   - Solar term calculations
   - Leap month determination logic

#### 🖼️ Frontend Changes

1. **🐉 Create Chinese Calendar Component** - `resources/views/livewire/chinese-calendar-converter.blade.php`
   - Display Chinese year, month, day
   - Show zodiac animal and element
   - Leap month indicators

2. **🇯🇵 Create Japanese Calendar Component** - `resources/views/livewire/japanese-calendar-converter.blade.php`
   - Era name display (both Japanese and romanized)
   - Imperial year calculation
   - Era transition information

3. **🐅 Create Zodiac Display Component** - `resources/views/components/chinese-zodiac.blade.php`
   - Visual zodiac animal representation
   - Element associations
   - Cultural information tooltips

#### 🧪 Testing
- 🌌 Astronomical calculation accuracy tests
- 📅 Era transition edge case testing
- 🌙 Leap month detection validation
- 🎎 Cultural accuracy verification

---

## 📁 Phase 3: Ancient Near Eastern Calendars

> **🎯 GOAL:** Implement Babylonian, Assyrian, and Ancient Macedonian calendar systems

> **📖 CONTEXT:** These are historical calendars requiring research into ancient astronomical practices and calendar structures.

> **📋 TASK:** Create historically accurate implementations based on archaeological and historical evidence

### ✅ Requirements
- [x] Babylonian calendar with astronomical month beginnings
- [x] Assyrian calendar variations
- [x] Ancient Macedonian calendar system
- [x] Historical accuracy within known limitations
- [x] Proper handling of incomplete historical data

### 📐 Guidelines
- ✅ Base implementations on scholarly research
- ✅ Document assumptions and limitations
- ✅ Provide historical context in results
- ✅ Handle uncertainty in ancient dating

### 🔧 Implementation Details

#### 🖥️ Backend Changes

1. **🏛️ Create Babylonian Calendar Service** - `app/Services/BabylonianCalendarService.php`
   - Lunar month calculations based on Babylonian astronomy
   - Intercalation rules for 19-year cycle
   - Month name translations (Nisannu, Ayaru, etc.)
   - New Year calculation (spring equinox)
   ```php
   public function convertToBabylonian(Carbon $date): array
   public function getBabylonianMonthName(int $month): string
   public function calculateIntercalation(int $year): bool
   ```

2. **⚔️ Create Assyrian Calendar Service** - `app/Services/AssyrianCalendarService.php`
   - Similar to Babylonian with regional variations
   - Different intercalation practices
   - Assyrian month names and regnal years
   ```php
   public function convertToAssyrian(Carbon $date): array
   public function getAssyrianEra(Carbon $date): string
   ```

3. **🏺 Create Ancient Macedonian Service** - `app/Services/AncientMacedonianService.php`
   - Macedonian month names (Dios, Apellaios, etc.)
   - Correlation with other Greek calendars
   - Alexander's calendar reforms
   ```php
   public function convertToMacedonian(Carbon $date): array
   public function getMacedonianSeason(int $month): string
   ```

4. **📚 Create Historical Calendar Data Model** - `app/Models/HistoricalCalendarData.php`
   - Store calendar system metadata
   - Uncertainty indicators
   - Source documentation references

5. **🌙 Create Ancient Calendar Utility** - `app/Services/AncientAstronomyService.php`
   - Common astronomical calculations for ancient calendars
   - Lunar month determination
   - Seasonal correlations

#### 🖼️ Frontend Changes

1. **🏛️ Create Historical Calendar Components:**
   - 🏛️ `resources/views/livewire/babylonian-calendar-converter.blade.php`
   - ⚔️ `resources/views/livewire/assyrian-calendar-converter.blade.php`
   - 🏺 `resources/views/livewire/macedonian-calendar-converter.blade.php`

2. **📖 Create Historical Context Component** - `resources/views/components/historical-calendar-context.blade.php`
   - Educational information about ancient calendars
   - Accuracy disclaimers
   - Historical background

3. **⚠️ Create Uncertainty Indicator Component** - `resources/views/components/conversion-uncertainty.blade.php`
   - Visual indicators for approximate dates
   - Explanation of limitations
   - Source citations

#### 🧪 Testing
- 📅 Historical date correlation tests
- 🏛️ Known historical event validation
- ⚠️ Edge case handling for incomplete data
- 📚 Accuracy documentation

---

## 📁 Phase 4: African and Regional Calendar Systems

> **🎯 GOAL:** Implement Coptic, Egyptian, and Ethiopian calendar systems

> **📖 CONTEXT:** These calendars have well-documented structures but require specific calculations for their unique year systems and month structures.

> **📋 TASK:** Create precise implementations for African calendar systems

### ✅ Requirements
- [x] Coptic calendar with proper leap year calculations
- [x] Ancient Egyptian calendar (both civil and lunar variants)
- [x] Ethiopian calendar with unique month structure
- [x] Regional timezone considerations
- [x] Cultural naming accuracy

### 📐 Guidelines
- ✅ Ensure religious and cultural sensitivity
- ✅ Implement proper leap year rules
- ✅ Handle calendar reforms historically
- ✅ Provide cultural context

### 🔧 Implementation Details

#### 🖥️ Backend Changes

1. **☦️ Create Coptic Calendar Service** - `app/Services/CopticCalendarService.php`
   - Era of Martyrs (Anno Martyrum) calculations
   - 13-month structure (12 months + Epagomenae)
   - Leap year rules (every 4 years without century exceptions)
   - Coptic month names (Thout, Paopi, etc.)
   ```php
   public function convertToCoptic(Carbon $date): array
   public function getCopticMonthName(int $month): string
   public function isLeapYear(int $year): bool
   ```

2. **🏺 Create Egyptian Calendar Service** - `app/Services/EgyptianCalendarService.php`
   - Civil calendar (365 days, no leap years)
   - Lunar calendar variant
   - Dynasty-based dating options
   - Ancient Egyptian month names and seasons
   ```php
   public function convertToEgyptian(Carbon $date): array
   public function getEgyptianSeason(int $month): string
   public function getDynastyYear(Carbon $date): string
   ```

3. **🇪🇹 Create Ethiopian Calendar Service** - `app/Services/EthiopianCalendarService.php`
   - 13-month structure similar to Coptic
   - Unique era calculation (7-8 years behind Gregorian)
   - Ethiopian month names (Meskerem, Tikemt, etc.)
   - Religious calendar correlations
   ```php
   public function convertToEthiopian(Carbon $date): array
   public function getEthiopianMonthName(int $month): string
   ```

4. **🌍 Create African Calendar Utility** - `app/Services/AfricanCalendarUtility.php`
   - Common calculations for African calendar systems
   - Shared leap year logic
   - Cultural naming conventions

#### 🖼️ Frontend Changes

1. **🌍 Create African Calendar Components:**
   - ☦️ `resources/views/livewire/coptic-calendar-converter.blade.php`
   - 🏺 `resources/views/livewire/egyptian-calendar-converter.blade.php`
   - 🇪🇹 `resources/views/livewire/ethiopian-calendar-converter.blade.php`

2. **📚 Create Cultural Information Component** - `resources/views/components/african-calendar-info.blade.php`
   - Religious significance
   - Cultural practices
   - Modern usage information

3. **📊 Create Month Structure Visualizer** - `resources/views/components/calendar-month-structure.blade.php`
   - Visual representation of 13-month calendars
   - Interactive month details
   - Cultural celebrations and observances

#### 🧪 Testing
- ⛪ Religious date correlation tests
- 📅 Leap year calculation validation
- 🎭 Cultural accuracy verification
- 🌍 Modern usage correlation tests

---

## 📁 Phase 5: Mesoamerican Calendar System

> **🎯 GOAL:** Implement Mayan calendar system with multiple interlocking cycles

> **📖 CONTEXT:** The Mayan calendar is highly complex with multiple concurrent cycles that must be synchronized properly.

> **📋 TASK:** Create accurate implementation of Mayan Long Count, Haab, and Tzolk'in calendars

### ✅ Requirements
- [x] Long Count calculations (baktun, katun, tun, winal, k'in)
- [x] Haab calendar (365-day solar year)
- [x] Tzolk'in calendar (260-day sacred year)
- [x] Calendar Round correlations
- [x] Mayan numeral system support
- [x] Archaeological correlation consensus

### 📐 Guidelines
- ✅ Use GMT (Goodman-Martinez-Thompson) correlation
- ✅ Implement all three calendar systems
- ✅ Provide educational context
- ✅ Handle pre-Columbian dating accurately

### 🔧 Implementation Details

#### 🖥️ Backend Changes

1. **🌋 Create Mayan Calendar Service** - `app/Services/MayanCalendarService.php`
   - Long Count calculations with proper correlation constant
   - Haab calendar with 18 months + Wayeb
   - Tzolk'in sacred calendar with 20 day names and 13 numbers
   - Calendar Round correlations (52-year cycle)
   ```php
   public function convertToLongCount(Carbon $date): array
   public function getHaab(Carbon $date): array
   public function getTzolkin(Carbon $date): array
   public function getCalendarRound(Carbon $date): array
   ```

2. **🔢 Create Mayan Numeral Service** - `app/Services/MayanNumeralService.php`
   - Vigesimal (base-20) number system
   - Mayan numeral representation
   - Glyph name correlations
   ```php
   public function convertToMayanNumerals(int $number): string
   public function getGlyphRepresentation(int $digit): string
   ```

3. **📚 Create Mayan Calendar Data Model** - `app/Models/MayanCalendarData.php`
   - Day names, month names
   - Cultural significance data
   - Archaeological correlation data

4. **⭐ Create Mayan Astronomical Service** - `app/Services/MayanAstronomyService.php`
   - Venus cycle calculations
   - Eclipse prediction tables
   - Seasonal correlations

#### 🖼️ Frontend Changes

1. **🌋 Create Mayan Calendar Component** - `resources/views/livewire/mayan-calendar-converter.blade.php`
   - Long Count display with proper notation
   - Haab and Tzolk'in synchronization
   - Calendar Round calculations
   - Mayan numeral display

2. **📚 Create Mayan Educational Component** - `resources/views/components/mayan-calendar-education.blade.php`
   - Explanation of calendar systems
   - Cultural and astronomical significance
   - Archaeological context

3. **⚙️ Create Calendar Wheel Visualizer** - `resources/views/components/mayan-calendar-wheels.blade.php`
   - Interactive Haab and Tzolk'in wheels
   - Visual Calendar Round representation
   - Sacred day combinations

#### 🧪 Testing
- 🏺 Archaeological date correlation tests
- 🔄 Calendar synchronization validation
- 📅 Known historical event verification
- 🔢 Mathematical accuracy of vigesimal system

---

## Technical Architecture

### Service Layer Structure
```
app/Services/
├── CalendarConversionService.php (Base)
├── JulianDayService.php
├── HebrewCalendarService.php
├── IslamicCalendarService.php
├── FrenchRepublicanService.php
├── ChineseCalendarService.php
├── JapaneseCalendarService.php
├── BabylonianCalendarService.php
├── AssyrianCalendarService.php
├── AncientMacedonianService.php
├── CopticCalendarService.php
├── EgyptianCalendarService.php
├── EthiopianCalendarService.php
└── MayanCalendarService.php
```

### API Structure
```
/api/calendar/convert/
├── julian-day
├── hebrew
├── islamic
├── french-republican
├── chinese
├── japanese
├── babylonian
├── assyrian
├── macedonian
├── coptic
├── egyptian
├── ethiopian
└── mayan
```

### Frontend Structure
```
resources/views/livewire/
├── calendar-converter.blade.php (Main)
├── julian-day-converter.blade.php
├── hebrew-calendar-converter.blade.php
├── islamic-calendar-converter.blade.php
├── french-republican-converter.blade.php
├── chinese-calendar-converter.blade.php
├── japanese-calendar-converter.blade.php
├── babylonian-calendar-converter.blade.php
├── assyrian-calendar-converter.blade.php
├── macedonian-calendar-converter.blade.php
├── coptic-calendar-converter.blade.php
├── egyptian-calendar-converter.blade.php
├── ethiopian-calendar-converter.blade.php
└── mayan-calendar-converter.blade.php
```

### Database Schema Additions
```sql
-- Japanese Eras
CREATE TABLE japanese_eras (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_japanese VARCHAR(50) NOT NULL,
    name_romanized VARCHAR(50) NOT NULL,
    name_english VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Historical Calendar Metadata
CREATE TABLE historical_calendar_data (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_system VARCHAR(50) NOT NULL,
    data_type VARCHAR(50) NOT NULL,
    key_name VARCHAR(100) NOT NULL,
    value TEXT NOT NULL,
    cultural_context TEXT NULL,
    source_reference TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Mayan Calendar Data
CREATE TABLE mayan_calendar_data (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_type ENUM('haab', 'tzolkin', 'long_count') NOT NULL,
    position INT NOT NULL,
    name_maya VARCHAR(50) NOT NULL,
    name_english VARCHAR(50) NOT NULL,
    cultural_significance TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

## Error Handling Strategy

### Backend Error Handling
- Custom exception classes for each calendar system
- Graceful degradation for incomplete historical data
- Proper HTTP status codes for API responses
- Detailed error messages with cultural context

### Frontend Error Handling
- Alpine.js error state management
- User-friendly error messages
- Fallback displays for conversion failures
- Loading states during calculations

## Testing Strategy

### Unit Tests
- Service layer calculations
- Edge case handling
- Historical accuracy validation
- Mathematical precision tests

### Feature Tests
- API endpoint functionality
- Complete conversion workflows
- Error handling scenarios
- Database interactions

### Browser Tests
- Livewire component interactions
- Alpine.js client-side behavior
- User experience flows
- Cross-browser compatibility

## Performance Considerations

### Backend Optimization
- Caching frequently requested conversions
- Database indexing for era lookups
- Efficient astronomical calculations
- Memory management for complex calculations

### Frontend Optimization
- Lazy loading of calendar components
- Alpine.js state management
- Debounced input handling
- Responsive loading indicators

## Cultural Sensitivity Guidelines

### Naming Conventions
- Use authentic cultural names where possible
- Provide romanized versions for accessibility
- Include cultural context and significance
- Respect religious and historical importance

### Documentation Standards
- Cite scholarly sources for implementations
- Acknowledge limitations and assumptions
- Provide historical context
- Include educational resources

## Maintenance and Extensibility

### Code Organization
- Modular service architecture
- Interface-based design patterns
- Consistent naming conventions
- Comprehensive documentation

### Future Extensions
- Additional regional calendars
- Improved astronomical calculations
- Enhanced cultural information
- Mobile app considerations

This implementation plan provides a comprehensive roadmap for creating a world-class calendar conversion system while maintaining the highest standards of cultural sensitivity, historical accuracy, and technical excellence.
