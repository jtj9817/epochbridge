<?php

namespace App\Jobs;

use App\Models\OnThisDayEntry;
use App\Services\Wikimedia\OnThisDayService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScrapeWikimediaBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $month,
        public int $batchSize = 5,
        public int $consecutiveFailures = 0
    ) {}

    /**
     * Execute the job.
     */
    public function handle(OnThisDayService $service): void
    {
        $logPrefix = "[WikimediaScraper|Month:{$this->month}]";
        
        // 1. Identify Missing Days
        // We use a leap year (2024) to ensure Feb 29 is included if we scrape Feb
        $daysInMonth = Carbon::createFromDate(2024, $this->month, 1)->daysInMonth;
        $allDays = range(1, $daysInMonth);
        
        $existingDays = OnThisDayEntry::query()
            ->where('month', $this->month)
            ->where('type', 'all')
            ->where('language', 'en')
            ->pluck('day')
            ->toArray();
            
        $missingDays = array_values(array_diff($allDays, $existingDays));
        $remainingCount = count($missingDays);

        Log::info("{$logPrefix} Starting batch. Remaining days to scrape: {$remainingCount}");

        // 2. Terminate if Complete
        if ($remainingCount === 0) {
            Log::info("{$logPrefix} All days covered for this month. Scraper job chain terminating successfully.");
            return;
        }

        // 3. Process Batch
        $batch = array_slice($missingDays, 0, $this->batchSize);
        $successCount = 0;
        $failCount = 0;

        foreach ($batch as $day) {
            // Check termination condition again in case of parallel execution artifacts (unlikely but safe)
            if (OnThisDayEntry::where('month', $this->month)->where('day', $day)->exists()) {
                continue;
            }

            Log::info("{$logPrefix} Scraping Day {$day}...");
            
            // Use 2024 to support Feb 29
            $date = Carbon::createFromDate(2024, $this->month, $day); 
            
            try {
                $result = $service->getForDate($date);
                
                if ($result['error']) {
                    Log::warning("{$logPrefix} Failed Day {$day}: {$result['error']}");
                    $failCount++;
                } else {
                    Log::info("{$logPrefix} Successfully scraped and saved Day {$day}");
                    $successCount++;
                }
            } catch (\Exception $e) {
                Log::error("{$logPrefix} Exception on Day {$day}: " . $e->getMessage());
                $failCount++;
            }
            
            // Gracious sleep between requests to respect rate limits
            // 5000 req/hr = ~1.4 req/sec. Sleeping 1 second + network time is safe.
            sleep(1);
        }

        // 4. Calculate Next Step (Dynamic Backoff)
        // If we made NO progress (all failed), we back off exponentially.
        $nextConsecutiveFailures = ($successCount === 0) ? $this->consecutiveFailures + 1 : 0;
        
        // Base delay 5 seconds. If failing, exponential backoff: 5, 10, 20, 40... max 1 hour.
        $delaySeconds = 5;
        if ($nextConsecutiveFailures > 0) {
            $delaySeconds = min(5 * (2 ** ($nextConsecutiveFailures - 1)), 3600);
            Log::warning("{$logPrefix} Batch failed completely. Backing off for {$delaySeconds}s before next attempt. Consecutive failures: {$nextConsecutiveFailures}");
        }

        // 5. Dispatch Next Batch
        // We always dispatch if there were missing days at the start. 
        // The next job will re-check missing days and might terminate if another process finished them, 
        // or continue if we still have work.
        Log::info("{$logPrefix} Batch complete (Success: {$successCount}, Fail: {$failCount}). Dispatching next batch in {$delaySeconds}s.");
        
        self::dispatch($this->month, $this->batchSize, $nextConsecutiveFailures)
            ->delay(now()->addSeconds($delaySeconds));
    }
}
