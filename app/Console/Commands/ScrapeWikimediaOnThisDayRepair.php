<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeWikimediaBatch;
use App\Models\OnThisDayEntry;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ScrapeWikimediaOnThisDayRepair extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:on-this-day:repair
                            {month? : The month number (1-12) to repair. Defaults to current month.}
                            {--all : Repair all 12 months}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Repairs invalid Wikimedia On This Day cache entries and re-dispatches scraper batches.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $months = $this->resolveMonths();

        if ($months === []) {
            return Command::FAILURE;
        }

        foreach ($months as $month) {
            $this->repairMonth((int) $month);
        }

        return Command::SUCCESS;
    }

    /**
     * @return array<int, int>
     */
    private function resolveMonths(): array
    {
        if ($this->option('all')) {
            return range(1, 12);
        }

        $month = $this->argument('month') ?? now()->month;

        if (! is_numeric($month) || $month < 1 || $month > 12) {
            $this->error('Invalid month provided. Please use a number between 1 and 12.');

            return [];
        }

        return [(int) $month];
    }

    private function repairMonth(int $month): void
    {
        $monthName = Carbon::createFromDate(null, $month, 1)->format('F');

        $invalidQuery = OnThisDayEntry::query()
            ->where('language', 'en')
            ->where('type', 'all')
            ->where('month', $month)
            ->where(function ($query) {
                $query->whereNull('day_info')
                    ->orWhere('day_info', '')
                    ->orWhereRaw('JSON_VALID(day_info) = 0');
            });

        $invalidCount = (clone $invalidQuery)->count();

        if ($invalidCount === 0) {
            $this->line("{$monthName} (Month {$month}): no invalid cache entries found.");

            return;
        }

        $deleted = $invalidQuery->delete();

        $this->info("{$monthName} (Month {$month}): removed {$deleted} invalid cache entries. Dispatching scraper batch.");

        ScrapeWikimediaBatch::dispatch($month);
    }
}
