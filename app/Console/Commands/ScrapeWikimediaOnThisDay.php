<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeWikimediaBatch;
use Illuminate\Console\Command;
use Carbon\Carbon;

class ScrapeWikimediaOnThisDay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:on-this-day 
                            {month? : The month number (1-12) to scrape. Defaults to current month.}
                            {--all : Scrape all 12 months}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initiates a scraping job chain for Wikimedia On This Day events.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->info('Dispatching scraper jobs for all 12 months...');
            for ($m = 1; $m <= 12; $m++) {
                ScrapeWikimediaBatch::dispatch($m);
                $this->line(" - Month $m dispatched.");
            }
            $this->info('All months dispatched to queue.');
            return;
        }

        $month = $this->argument('month') ?? now()->month;

        if (! is_numeric($month) || $month < 1 || $month > 12) {
            $this->error('Invalid month provided. Please use a number between 1 and 12.');
            return;
        }

        $monthName = Carbon::createFromDate(null, $month, 1)->format('F');
        
        $this->info("Dispatching scraper job for $monthName (Month $month)...");
        
        ScrapeWikimediaBatch::dispatch((int) $month);
        
        $this->info('Job dispatched! Check logs for progress.');
    }
}
