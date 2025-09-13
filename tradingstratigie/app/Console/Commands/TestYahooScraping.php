<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YahooScrapingService;

class TestYahooScraping extends Command
{
    protected $signature = 'test:yahoo-scraping {symbol=AAPL}';
    protected $description = 'Test Yahoo Finance web scraping to check if we can extract financial data';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        
        $this->info("🕷️ Testing Yahoo Finance web scraping for: {$symbol}");
        $this->newLine();

        $scrapingService = new YahooScrapingService();
        
        $this->line("Scraping Yahoo Finance website...");
        $data = $scrapingService->scrapeStockData($symbol);
        
        if ($data) {
            $this->info("✅ Successfully scraped data:");
            $this->newLine();
            
            $this->table(
                ['Field', 'Value'],
                [
                    ['Symbol', $data['symbol']],
                    ['Current Price', $data['current_price'] ? '$' . number_format($data['current_price'], 2) : 'N/A'],
                    ['Market Cap', $data['market_cap'] ? '$' . number_format($data['market_cap']) : 'N/A'],
                    ['Volume', $data['volume'] ? number_format($data['volume']) : 'N/A'],
                    ['P/E Ratio', $data['pe_ratio'] ?? 'N/A'],
                    ['Dividend Yield', $data['dividend_yield'] ? $data['dividend_yield'] . '%' : 'N/A'],
                    ['52W High', $data['fifty_two_week_high'] ? '$' . number_format($data['fifty_two_week_high'], 2) : 'N/A'],
                    ['52W Low', $data['fifty_two_week_low'] ? '$' . number_format($data['fifty_two_week_low'], 2) : 'N/A'],
                    ['Beta', $data['beta'] ?? 'N/A'],
                    ['EPS', $data['eps'] ?? 'N/A'],
                ]
            );
        } else {
            $this->error("❌ Failed to scrape data from Yahoo Finance");
        }
        
        $this->newLine();
        $this->info("🏁 Scraping test completed!");
    }
}
