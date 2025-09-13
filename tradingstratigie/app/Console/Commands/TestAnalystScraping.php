<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YahooScrapingService;

class TestAnalystScraping extends Command
{
    protected $signature = 'test:analyst-scraping {symbol=AAPL}';
    protected $description = 'Test Yahoo Finance analyst data scraping';

    public function handle()
    {
        $symbol = strtoupper($this->argument('symbol'));
        $this->info("🎯 Testing Yahoo Finance analyst scraping for: {$symbol}");
        $this->newLine();

        $scrapingService = new YahooScrapingService();
        
        $this->line("Scraping analyst data from Yahoo Finance Analysis page...");
        $analystData = $scrapingService->scrapeAnalystData($symbol);

        if ($analystData) {
            $this->info("✅ Successfully scraped analyst data:");
            $this->newLine();

            $this->table(
                ['Field', 'Value'],
                [
                    ['Symbol', $analystData['symbol']],
                    ['Mean Price Target', $analystData['price_target_mean'] ? '$' . number_format($analystData['price_target_mean'], 2) : 'N/A'],
                    ['High Price Target', $analystData['price_target_high'] ? '$' . number_format($analystData['price_target_high'], 2) : 'N/A'],
                    ['Low Price Target', $analystData['price_target_low'] ? '$' . number_format($analystData['price_target_low'], 2) : 'N/A'],
                    ['Recommendation', $analystData['recommendation'] ?? 'N/A'],
                    ['Analyst Count', $analystData['analyst_count'] ?? 'N/A'],
                    ['Scraped At', $analystData['scraped_at']],
                ]
            );
        } else {
            $this->error("❌ Failed to scrape analyst data");
        }

        $this->newLine();
        $this->line("🏁 Analyst scraping test completed!");
    }
}
