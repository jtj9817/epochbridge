# Wikimedia "On This Day" Scraper Architecture

## 1. Overview

This document details the architecture of the **State-Aware Recursive Scraper** designed to populate the `file` table (via the `OnThisDayEntry` model) with historical event data from the Wikimedia API.

The system is designed as a **one-time population tool** that runs robustly within Laravel's queue infrastructure, respecting strict API rate limits and handling network instability without manual intervention.

## 2. Core Architecture: State-Aware Recursive Batching

Unlike traditional loops that risk time-outs, this scraper uses a recursive job pattern. Each job is "state-aware"—it determines its own work based on the current database state rather than passed arguments.

### The Workflow

1. **Dispatch**: The User triggers the Artisan command.
2. **Analyze**: The Job calculates `Missing Days = All Days in Month (using a leap-year baseline so Feb 29 is included) - Existing DB Entries for language=en/type=all`.
3. **Terminate**: If `Missing Days == 0`, the job logs `[WikimediaScraper|Month:X] Complete` and stops.
4. **Process**: The Job selects the next **5 missing days** (Batch Size).
5. **Scrape**: It fetches data for these days, sleeping **1 second** between requests.
6. **Recurse**: The Job dispatches a _new instance_ of itself to the queue to handle the next batch.

### Why this approach?

* **Timeouts**: Large months (31 days) would timeout a single synchronous process. Batches of 5 run in ~10 seconds, well under the 60-120s worker timeout.
* **Resumability**: If the queue worker crashes, the next triggered job (or manual restart) instantly picks up exactly where it left off because it calculates work based on the DB state.

## 3. Component Breakdown

### A. The Command

**Class:** `App\Console\Commands\ScrapeWikimediaOnThisDay`

**Signature:** `scrape:on-this-day {month?} {--all}`

* Acts as the trigger.
* Validates the month input (1-12).
* Dispatches the initial `ScrapeWikimediaBatch` job.

### B. The Job

**Class:** `App\Jobs\ScrapeWikimediaBatch`

* **Responsibility**: The brain of the operation.
* **Key Logic**:
    * Calculates set difference between *Expected Days* (days in month from a leap-year baseline, so Feb 29 is included) and *Stored Days* (language=en/type=all).
    * Implements **Exponential Backoff** (see Section 4).
    * Self-dispatches upon completion of a batch.

### C. The Service

**Class:** `App\Services\Wikimedia\OnThisDayService`

* **Responsibility**: The execution arm.
* **Enhancement**: Uses Laravel's `Http::retry(3, 1000)` to handle transient network errors (DNS, 503s) at the request level before they even reach the Job logic.

## 4. Resilience & Rate Limiting

The Wikimedia API limits personal tokens to **5,000 requests/hour** (~1.3 requests/second).

| Mechanism | Implementation | Purpose |
| :--- | :--- | :--- |
| **Micro-Sleeps** | `sleep(1)` | Pauses execution inside the loop between every API call to ensure we never exceed ~1 req/sec. |
| **Request Retry** | `Http::retry(3)` | Automatically retries failed HTTP packets (e.g., connection reset) without failing the job. |
| **Job Backoff** | `delay($seconds)` | If a batch fails entirely (0 successes), the next job is delayed by `5 * 2^failures` seconds (5s, 10s, 20s...). |

## 5. Operational Guide

### Running the Scraper

**Current Month:**
```bash
php artisan scrape:on-this-day
```

**Specific Month (e.g., December):**
```bash
php artisan scrape:on-this-day 12
```

**Full Year (All 12 Months):**
```bash
php artisan scrape:on-this-day --all
```

### Monitoring

Monitor the process via laravel.log or Pail. Search for the tag [WikimediaScraper].

**Log Examples:**

> [WikimediaScraper|Month:10] Starting batch. Remaining days to scrape: 31
> [WikimediaScraper|Month:10] Successfully scraped and saved Day 1
> [WikimediaScraper|Month:10] Batch complete. Dispatching next batch in 5s.
> [WikimediaScraper|Month:10] Complete

## 6. Database Schema

Data is stored in the `file` table.

* **Unique Constraint**: ['language', 'type', 'month', 'day']
* **Model**: `App\Models\OnThisDayEntry`

This constraint ensures idempotency. If a job accidentally runs for a day that was just saved by another process, the database rejects the duplicate, and the scraper moves on.

## 7. Implementation Verification & Remediation Plan

### Context: Plan Alignment

* The command signature and month validation match the documented interface, dispatching the initial batch job as described.
* The job is state-aware, processes 5-day batches, sleeps 1s between requests, and self-dispatches to continue work.
* Exponential backoff for fully failed batches and `Http::retry(3, 1000)` for API calls are in place.
* The `file` table and unique constraint (`language`, `type`, `month`, `day`) align with the idempotency model.

### Context: Current Gaps & Deviations

* The per-day existence check is not scoped to language/type, which can skip required `en/all` fetches if other rows exist.
* Termination logs do not currently emit the exact `Complete` text expected by tooling.
* Invalid cached JSON rows are treated as complete by the batch job and are never repaired.

### Plan (No Test Coverage)

1. **Scope Enforcement (en/all only)**: Align the per-day existence check with the `language=en` and `type=all` scope so it cannot skip required work for the intended dataset.
2. **Termination Log Consistency**: Update the termination log to emit the exact `Complete` text with the standard prefix (`[WikimediaScraper|Month:X] Complete`) for tooling consistency.
3. **Leap-Day Inclusion**: Keep the leap-year baseline for day calculations and document it explicitly (Feb 29 is always included when scraping February).
4. **Invalid JSON Repair Path**: Treat invalid cached JSON rows as missing and re-fetch them during batch processing (or add a dedicated repair mode that re-scrapes invalid entries).
5. **Manual Verification**: Use a short manual run (single month) to confirm log output, Feb 29 handling, and that invalid JSON rows are repaired; no automated test additions are planned.

### Concrete Repair Path (Invalid Cached JSON)

* **Strategy**: Move JSON validity checks out of the batch job and into a scheduled repair command that uses database-side validation, avoiding in-memory decoding.
* **Scheduled Repair Command**:
    * Command signature: `scrape:on-this-day:repair {month?} {--all}` that scopes to `language=en` and `type=all`.
    * Identify invalid rows using SQL-level checks (no PHP decoding):  
      `whereNull('day_info')->orWhere('day_info', '')->orWhereRaw('JSON_VALID(day_info) = 0')`
    * For each affected month, delete the invalid rows (or mark them) and dispatch `ScrapeWikimediaBatch` so the normal missing-day logic re-fetches them.
* **Scheduling**: Run nightly or weekly via the scheduler, e.g. `Schedule::command('scrape:on-this-day:repair --all')->dailyAt('02:00')`.
    * Laravel 12 placement: add the schedule entry in `routes/console.php`, or use `->withSchedule(...)` in `bootstrap/app.php`.
* **DB Compatibility**: If `JSON_VALID` is unavailable, add a `day_info_valid` boolean column updated on write and have the repair command query that flag instead.
