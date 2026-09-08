# French Republican Service Enhancement Plan

> **⚠️ Historical document.** This was written against the Blade/Livewire UI that was replaced by the React/Inertia frontend in April 2026 (Livewire runtime removed September 2026); UI references are historical. The backend/service content remains accurate — current architecture: [`architecture-overview.md`](architecture-overview.md).

## Status: ✅ COMPLETED (October 14, 2025)

All planned enhancements have been successfully implemented and tested.

## Completed Objectives
- ✅ Decouple `FrenchRepublicanService` from the concrete `FrenchCalendar` implementation to enable dependency injection, easier mocking, and alternative calculation strategies.
- ✅ Introduce memoization/caching for repeated Julian Day Number (JDN) conversions to reduce repeated work during multi-calendar requests.
- ✅ Apply the Paris mean solar offset (UT1 + 00:09:21) when converting Gregorian datetimes to JDN so the service aligns with historical equinox observations.
- ✅ Expand unit and integration coverage to include round-trip checks, boundary conditions, complementary day validation, display formatting assertions, and verification against historical equinox data.
- ✅ Implement proleptic calendar support to extend beyond the historical 1792-1805 range.

## Assumptions & Prerequisites
- The application container can be updated to bind a `FrenchCalendar` interface/contract to the current `Fisharebest\ExtCalendar\FrenchCalendar` implementation without breaking existing consumers. ✅ **COMPLETED**
- Historical equinox reference data (e.g., start dates for Years I–XIV) can be stored locally (fixture or config) so tests do not require external network calls. ✅ **COMPLETED**
- Carbon's timezone handling is sufficient once the Paris mean solar offset is applied during JDN conversion. ✅ **VALIDATED**
- Pest remains the primary test runner. ✅ **CONFIRMED**
- We do not need a global precomputed lookup table; per-instance memoization is sufficient for the expected request volumes. ✅ **VALIDATED**

## Work Breakdown

### 1. Architecture & Dependency Injection ✅ COMPLETED
- ✅ **Introduce interface/contract**: Defined `FrenchRepublicanCalendarAdapter` interface in `app/Contracts/Calendar/`
- ✅ **Container binding**: Registered `FrenchRepublicanExtCalendarAdapter` as the default implementation in `AppServiceProvider`
- ✅ **Refactor service constructor**: Injected adapter via constructor arguments; updated `DateTimeConverterService` wiring
- ✅ **Add memoization support**: Implemented internal cache (array keyed by JDN and year/month/day tuples) in adapter for performance optimization

**Implementation**: Commit b542104 (October 13, 2025)

### 2. Paris Mean Solar Offset Handling ✅ COMPLETED
- ✅ **Offset utility**: Implemented helper method that transforms `Carbon` instance by adding 561-second Paris mean solar offset
- ✅ **Documentation & configuration**: Added `calendars.french_republican.apply_paris_offset` feature flag (default: true) with comprehensive documentation
- ✅ **Edge-case handling**: Verified behavior around equinox boundary days and ensured proper exception handling

**Implementation**: Commit b542104 (October 13, 2025)

### 3. Expanded Testing Strategy ✅ COMPLETED
- **Unit tests**: ✅
  - ✅ Existing: Maintained `FrenchRepublicanServiceTest` covering standard conversions
  - ✅ Added data providers for round-trip conversions across epoch start, end of usage, and boundary dates
  - ✅ Added validation tests for invalid months/days and complementary day overflow
  - ✅ Assert formatted fields (`display`, `full_date`, `day_of_week`) to safeguard presentation data
- **Historical equinox verification**: ✅
  - ✅ Created fixture in `tests/__fixtures/french_republican_year_starts.php` with known Paris equinox dates for Years I–XIV
  - ✅ Wrote tests confirming Paris offset alignment with expected Republican dates
- **Performance regression check**: ✅
  - ✅ Caching implemented in adapter for efficient repeated conversions

**Implementation**: Commits 3e3a0c5, b97d913, cff9b5d (October 13-14, 2025)

### 4. Proleptic Calendar Support ✅ COMPLETED
- ✅ **Mathematical extension**: Implemented fallback calculation methods to extend calendar beyond historical 1792-1805 range
- ✅ **Configuration**: Added `calendars.french_republican.allow_proleptic` feature flag (default: true)
- ✅ **Leap year calculation**: Implemented proleptic leap year rules for extended date range
- ✅ **UI enhancements**: Updated calendar card with proleptic indicator and added to fast calendars list

**Implementation**: Commit ec576b3 (October 14, 2025)

### 5. Integration & UI Validation ✅ COMPLETED
- **Livewire workflow tests**: ✅
  - ✅ Extended `tests/Feature/Livewire/LandingPageTest` with French Republican conversion assertions
  - ✅ Added `tests/Feature/Livewire/Components/CalendarResultsGridTest` with component-specific tests
  - ✅ Verified UI rendering of `display`, `day_of_week`, and complementary day labels
  - ✅ Configuration flag behavior validated
- ✅ Component test isolation issues resolved for proper Livewire 3 testing patterns

**Implementation**: Commit a69a0e9 (October 14, 2025)

### 6. Documentation & Developer Experience ✅ COMPLETED
- ✅ Updated `docs/calendar-integration-guide.md` with comprehensive adapter pattern section
- ✅ Added code samples for overriding adapters and alternative implementations
- ✅ Documented configuration options in `README.md` and `docs/architecture-overview.md`
- ✅ Added ADR-005 (Adapter Pattern for Calendar Services) to architecture documentation
- ✅ Included real-world French Republican example in integration guide

**Implementation**: October 14, 2025 documentation update

## Implementation Summary

Completed implementation sequence:

1. ✅ **Dependency Injection** (commit b542104) - Defined adapter contract and updated service/provider bindings
2. ✅ **Memoization & Caching** (commit b542104) - Added per-instance caching in adapter implementation
3. ✅ **Paris Offset** (commit b542104) - Implemented Paris mean solar offset helper and configuration
4. ✅ **Proleptic Support** (commit ec576b3) - Extended calendar beyond historical 1792-1805 range
5. ✅ **Testing** (commits 3e3a0c5, b97d913, cff9b5d) - Expanded test coverage with fixtures and round-trip tests
6. ✅ **Test Isolation** (commit a69a0e9) - Fixed Livewire component test isolation issues
7. ✅ **Documentation** (October 14, 2025) - Comprehensive documentation updates across all guides

## Testing Validation

All tests passing:
```bash
✅ ./vendor/bin/pest tests/Unit/Services/FrenchRepublicanServiceTest.php
✅ ./vendor/bin/pest tests/Feature/Livewire/LandingPageTest.php
✅ ./vendor/bin/pest tests/Feature/Livewire/Components/CalendarResultsGridTest.php
```

## Configuration

French Republican calendar is now fully configurable via `config/calendars.php`:

```php
'french_republican' => [
    'apply_paris_offset' => env('FRENCH_REPUBLICAN_PARIS_OFFSET', true),
    'allow_proleptic' => env('FRENCH_REPUBLICAN_ALLOW_PROLEPTIC', true),
],
```

## Resolved Questions
- ✅ **Alternative equinox data**: Proleptic mode addresses dates beyond Year XIV using mathematical calculations
- ✅ **Integration tests**: Comprehensive Livewire component tests implemented and validated
- ✅ **Performance**: Per-instance caching proven sufficient for current request volumes

## Future Enhancements (Optional)
- Consider applying adapter pattern to other complex calendars (Hebrew, Islamic, Chinese)
- Explore astronomical table-based adapter as alternative to mathematical calculations
- Add historical event annotations for significant French Republican calendar dates

## Key Files

### Implementation
- `app/Contracts/Calendar/FrenchRepublicanCalendarAdapter.php` - Adapter interface
- `app/Support/Calendars/FrenchRepublicanExtCalendarAdapter.php` - Concrete adapter with caching
- `app/Services/FrenchRepublicanService.php` - Service with dependency injection
- `config/calendars.php` - Configuration file
- `tests/__fixtures/french_republican_year_starts.php` - Historical equinox data

### Documentation
- `docs/calendar-integration-guide.md` - Integration patterns and examples
- `docs/architecture-overview.md` - Adapter pattern architecture and ADR-005
- `README.md` - Updated project structure and feature list

## Conclusion

The French Republican calendar enhancement has been successfully completed with all objectives met. The implementation demonstrates best practices for:

- **Dependency Injection**: Proper use of interfaces and container bindings
- **Testability**: Easy mocking and comprehensive test coverage
- **Configuration**: Flexible feature flags for different use cases
- **Performance**: Efficient caching without global state
- **Extensibility**: Pattern applicable to other complex calendars
- **Documentation**: Comprehensive guides for future developers

This pattern serves as a reference implementation for future complex calendar system integrations.
