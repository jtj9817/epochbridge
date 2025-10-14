# French Republican Service Enhancement Plan

## Objectives
- Decouple `FrenchRepublicanService` from the concrete `FrenchCalendar` implementation to enable dependency injection, easier mocking, and alternative calculation strategies.
- Introduce memoization/caching for repeated Julian Day Number (JDN) conversions to reduce repeated work during multi-calendar requests.
- Apply the Paris mean solar offset (UT1 + 00:09:21) when converting Gregorian datetimes to JDN so the service aligns with historical equinox observations.
- Expand unit and integration coverage to include round-trip checks, boundary conditions, complementary day validation, display formatting assertions, and verification against historical equinox data.

## Assumptions & Prerequisites
- The application container can be updated to bind a `FrenchCalendar` interface/contract to the current `Fisharebest\ExtCalendar\FrenchCalendar` implementation without breaking existing consumers.
- Historical equinox reference data (e.g., start dates for Years I–XIV) can be stored locally (fixture or config) so tests do not require external network calls.
- Carbon’s timezone handling is sufficient once the Paris mean solar offset is applied during JDN conversion.
- Pest remains the primary test runner.

## Work Breakdown

### 1. Architecture & Dependency Injection
- **Introduce interface/contract**: Define a lightweight contract (e.g., `FrenchRepublicanCalendarAdapter`) that exposes `jdToYmd`, `ymdToJd`, `jdStart`, `jdEnd`, and `isLeapYear`.
- **Container binding**: Register the existing `Fisharebest\ExtCalendar\FrenchCalendar` as the default implementation in a service provider. Allow future swapping (e.g., astronomical vs. arithmetic adapters).
- **Refactor service constructor**: Inject the adapter via constructor arguments; update `DateTimeConverterService` wiring accordingly.
- **Add memoization support**: Implement an internal cache (array keyed by JDN and year/month/day tuples) for conversions performed within a single request lifecycle. Ensure cache is cleared per instance.

### 2. Paris Mean Solar Offset Handling
- **Offset utility**: Create a helper method (or dedicated utility class) that transforms a `Carbon` instance by adding the 561-second Paris mean solar offset before JDN calculation.
- **Documentation & configuration**: Expose a configuration flag (e.g., `calendars.french_republican.apply_paris_offset`) defaulting to true for historical accuracy, with docs explaining implications.
- **Edge-case handling**: Verify behavior around equinox boundary days (Sept 20–24) and ensure range exceptions still trigger when outside Years I–XIV.

### 3. Expanded Testing Strategy
- **Unit tests**:
  - ✔️ Existing: keep the newly added `FrenchRepublicanServiceTest` covering standard conversions.
  - ➕ Add data providers for round-trip conversions (Gregorian → FR → Gregorian) across: epoch start (1792‑09‑22), end of official usage (1805‑12‑31), start/end of each month, complementary days in leap and common years.
  - ➕ Add validation tests for invalid months/days, especially complementary day overflow in non-leap years.
  - ➕ Assert formatted fields (`display`, `full_date`, `day_of_week`) to safeguard presentation data.
- **Historical equinox verification**:
  - Create a fixture (CSV/array) containing known Paris equinox start dates for Years I–XIV.
  - Write tests confirming that applying the Paris offset aligns Gregorian inputs with the expected Republican year/day (e.g., Year I starts on 1792‑09‑22, Year VII leap day on 1799‑09‑22).
- **Performance regression check**:
  - Add a smoke test ensuring multiple conversions in a loop reuse cached results (could assert on internal counter via a spy or expose metrics through the adapter).

### 4. Integration & UI Validation
- **Livewire workflow tests**: Extend the existing Livewire feature specs (e.g., `tests/Feature/Livewire/LandingPageTest`) to:
  - Submit a Gregorian date within the supported range and assert that the French Republican card renders the expected `display`, `day_of_week`, and complementary day labels.
  - Toggle the new Paris offset configuration flag off and verify the UI reflects the shifted result (guarding that the offset logic is applied when enabled).
  - Exercise autocomplete/quick actions to ensure cached conversions surface correctly in the interface.
- Consider adding a Dusk scenario for full-stack verification if visual regression confidence is needed.

### 5. Documentation & Developer Experience
- Update `docs/calendar-integration-guide.md` (or create a focused subsection) to describe the dependency injection approach, the Paris offset, testing fixtures, and Livewire integration checks.
- Provide code samples showing how to override the adapter for alternative calculation methods (e.g., astronomical table vs. arithmetic rule) if future work requires it.
- Mention new configuration options in the README or appropriate docs.

## Sequencing
1. Define adapter contract and update service/provider bindings.
2. Add memoization and ensure existing tests still pass.
3. Implement Paris mean solar offset helper and wire it into `fromGregorian`.
4. Create historical equinox fixtures and expand unit tests (round trips, boundaries, formatting).
5. Document changes and expose configuration toggles alongside UI/integration guidance.

## Testing Checklist
- `./vendor/bin/pest tests/Unit/Services/FrenchRepublicanServiceTest.php`
- `./vendor/bin/pest --group=french-republican` *(new group tag for expanded tests)*
- Optional smoke test for multi-calendar conversions: `./vendor/bin/pest tests/Unit/Services/DateTimeConverterServiceTest.php`

## Open Questions
- Should we support alternative equinox data beyond Year XIV (e.g., proleptic extension) behind a feature flag?
- Do we need integration tests that drive the Livewire UI to confirm the new display/offset behavior?
- Is a cached lookup table preferable to on-the-fly Paris offset adjustments for large batch conversions?
