# Performance Optimization Guide

This document outlines the performance strategies implemented in EpochBridge.

## Conversion Caching

The most computationally expensive part of the application is date conversion. In `app/Services/DateTimeConverterService.php`, results are cached with Laravel's cache (`Cache::remember`, 1-hour TTL).

- **Cache keys**: `'conversion_fast_' . md5($dateString . $inputType)` for the primary tier and `'conversion_additional_' . md5(...)` for the additional tier.
- **Two-tier split**: the primary ("fast") tier covers the common calendars (including the `fastCalendars` set — hebrew, islamic, french_republican, coptic, ethiopian — plus the built-in Gregorian/Jalali/Unix/ISO 8601 outputs); the additional tier covers the remaining calendar services. The JSON API merges both via `getFullConversionResults()`.
- **Time-precise inputs skip the cache**: for `unix` and `iso8601` input types every distinct value yields a distinct result, so a cached entry would never be reused while the cache-key space grows without bound (`TIME_PRECISE_INPUT_TYPES` in `DateTimeConverterService`). These requests always compute fresh.

## Rate Limiting

Public frontend endpoints (`POST /frontend/conversions`, `GET /frontend/on-this-day`, `GET /frontend/quick-action`) are throttled by the `frontend-public` limiter (60 requests/minute/IP, defined in `AppServiceProvider`). This protects the conversion cache and the Wikimedia API budget from abuse.

## On This Day Caching

Wikimedia "On This Day" responses are cached in the `file` table by `OnThisDayCacheService` (unique per language/type/month/day), so repeat visits for the same date never re-hit the Wikimedia API. A nightly `scrape:on-this-day:repair --all` scheduler keeps entries fresh. See [wikimedia-scraper-architecture.md](wikimedia-scraper-architecture.md).

## Frontend Optimizations

- **Vite production build**: `npm run build` compiles, tree-shakes, and minifies; Tailwind CSS 4 purges unused styles.
- **Client-side filtering**: search and calendar-type filtering (`CalendarSearchFilter`) run entirely in React over already-fetched results — no extra requests.
- **Loading states**: conversion requests set an `isConverting` flag that disables duplicate submits and shows feedback while the fetch is in flight.
- **Bounded payloads**: saved-conversion requests are capped server-side (≤128 keys, ≤64 KiB per payload, notes ≤2048 chars), keeping request/response sizes small.

## Database

- Saved conversions list paginates at 20/page and is covered by indexes on `(user_id, created_at)`, `user_id`, and `created_at`; a unique `(user_id, original_input)` constraint doubles as the dedupe key.
