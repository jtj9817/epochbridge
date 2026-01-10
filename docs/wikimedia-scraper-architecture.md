# Wikimedia "On This Day" Scraper Architecture

## 1. Overview

This document details the architecture of the **State-Aware Recursive Scraper** designed to populate the `file` table (via the `OnThisDayEntry` model) with historical event data from the Wikimedia API.

The system is designed as a **one-time population tool** that runs robustly within Laravel's queue infrastructure, respecting strict API rate limits and handling network instability without manual intervention.

## 2. Core Architecture: State-Aware Recursive Batching

Unlike traditional loops that risk time-outs, this scraper uses a recursive job pattern. Each job is "state-aware"—it determines its own work based on the current database state rather than passed arguments.

### The Workflow

1. **Dispatch**: The User triggers the Artisan command.
2. **Analyze**: The Job calculates `Missing Days = All Days in Month - Existing DB Entries`.
3. **Terminate**: If `Missing Days == 0`, the job logs `"Complete"` and stops.
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
    * Calculates set difference between *Expected Days* (1-31) and *Stored Days*.
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

## 6. Database Schema

Data is stored in the `file` table.

* **Unique Constraint**: ['language', 'type', 'month', 'day']
* **Model**: `App\Models\OnThisDayEntry`

This constraint ensures idempotency. If a job accidentally runs for a day that was just saved by another process, the database rejects the duplicate, and the scraper moves on.
