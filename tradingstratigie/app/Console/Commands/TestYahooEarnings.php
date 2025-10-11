<?php

namespace App\Console\Commands;

use App\Services\YahooScrapingService;
use Illuminate\Console\Command;

class TestYahooEarnings extends Command
{
    protected $signature = 'test:yahoo-earnings {symbol=TSLA}';
    protected $description = 'Test Yahoo Finance earnings date scraping';

    public function handle(YahooScrapingService $yahooService)
    {
        $symbol = $this->argument('symbol');
        
        $this->info("Testing Yahoo Finance earnings scraping for {$symbol}...");
        $this->newLine();
        
        $startTime = microtime(true);
        $earningsData = $yahooService->scrapeEarningsDate($symbol);
        $executionTime = round(microtime(true) - $startTime, 2);
        
        if ($earningsData) {
            $this->info("✅ Successfully scraped earnings data!");
            $this->newLine();
            
            $this->table(
                ['Field', 'Value'],
                [
                    ['Earnings Date', $earningsData['earnings_date']],
                    ['Date Text', $earningsData['date_text']],
                    ['Source', $earningsData['source']],
                    ['Scraped At', $earningsData['scraped_at']],
                ]
            );
            
            $this->newLine();
            $this->info("Execution time: {$executionTime} seconds");
            
            return 0;
        } else {
            $this->error("❌ No earnings date found for {$symbol}");
            $this->warn("This could mean:");
            $this->line("  • No upcoming earnings scheduled");
            $this->line("  • Yahoo Finance doesn't have the data");
            $this->line("  • The scraping pattern needs adjustment");
            
            return 1;
        }
    }
}
