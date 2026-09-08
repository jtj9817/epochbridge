# Cultural & Historical Accuracy Guidelines

Maintaining accuracy and sensitivity is paramount for a tool like EpochBridge. This guide provides principles for developers when working with different calendar systems.

## Naming Conventions

-   **Use Authentic Names**: Whenever possible, use the authentic, native names for months and days of the week (e.g., `Mäskäräm` for Ethiopian, `Vendémiaire` for French Republican).
-   **Provide Transliterations**: Always provide an English transliteration or common name alongside the native name to ensure accessibility for a wider audience (e.g., displaying both `مرداد` and `Mordad` for Jalali).
-   **Character Encoding**: Ensure all views and services use UTF-8 to correctly handle non-Latin scripts.

## Historical Context & Disclaimers

Many ancient and historical calendars are not perfectly understood, and scholarly consensus can vary. It's crucial to be transparent about this.

-   **Approximations**: For calendars like Babylonian or Mayan, where conversions rely on approximations or correlation constants, this must be clearly stated.
-   **Use Disclaimers**: The React `BaseCalendarCard` component accepts a `disclaimer` prop (populated automatically by `GenericCalendarCard` when the calendar data includes one). Use it to provide brief context or acknowledge scholarly uncertainty.
    -   *Example (Babylonian)*: "This is a scholarly approximation. Exact dates are subject to interpretation of cuneiform tablets."
-   **Cite Sources**: In the service class comments for complex historical calendars, cite the primary algorithms, constants, or scholarly sources used for the implementation.

## Timezone Sensitivity

-   **Calendar-Specific Timezones**: Many calendars are tied to a specific geographical location for their calculations (e.g., Jalali to `Asia/Tehran`, Hebrew to `Asia/Jerusalem`). The `ensureTimezone()` helper in `CalendarConversionService` must be used to ensure calculations are performed in the correct context.
-   **UTC for Storage**: While calculations are timezone-specific, standardized outputs like ISO 8601 should always be in UTC for interoperability.

## Implementation Principles

-   **Avoid Anachronisms**: Be careful not to apply modern concepts like timezones to ancient calendars where they would not have existed. Our implementation uses timezones as a necessary bridge for conversion but should acknowledge this is a modern convenience.
-   **Peer Review**: Any new implementation of a historical or culturally significant calendar should be reviewed by another developer, with a focus on checking the algorithms and naming against reliable sources.
