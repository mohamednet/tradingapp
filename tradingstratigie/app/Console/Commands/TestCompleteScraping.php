<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YahooScrapingService;

class TestCompleteScraping extends Command
{
    protected $signature = 'test:complete-scraping {symbol=AAPL}';
    protected $description = 'Test complete Yahoo Finance scraping (stock data + analyst data + performance)';

    public function handle()
    {
        $symbol = strtoupper($this->argument('symbol'));
        $this->info("🚀 Testing complete Yahoo Finance scraping for: {$symbol}");
        $this->newLine();

        $scrapingService = new YahooScrapingService();
        
        // Test stock data scraping
        $this->line("📊 Scraping stock data...");
        $stockData = $scrapingService->scrapeStockData($symbol);

        if ($stockData) {
            $this->info("✅ Stock data scraped successfully");
            
            // Test price performance calculation
            if ($stockData['current_price']) {
                $this->line("📈 Calculating price performance...");
                $performance = $scrapingService->calculatePricePerformance($symbol, $stockData['current_price']);
                $stockData = array_merge($stockData, $performance);
            }
        }

        // Test analyst data scraping
        $this->line("🎯 Scraping analyst data...");
        $analystData = $scrapingService->scrapeAnalystData($symbol);

        if ($analystData) {
            $this->info("✅ Analyst data scraped successfully");
        }

        // Display combined results
        if ($stockData || $analystData) {
            $this->newLine();
            $this->info("📋 Complete Financial Data Summary:");
            $this->newLine();

            $tableData = [];
            
            if ($stockData) {
                $tableData[] = ['Current Price', $stockData['current_price'] ? '$' . number_format($stockData['current_price'], 2) : 'N/A'];
                $tableData[] = ['Market Cap', $stockData['market_cap'] ? '$' . number_format($stockData['market_cap']) : 'N/A'];
                $tableData[] = ['Volume', $stockData['volume'] ? number_format($stockData['volume']) : 'N/A'];
                $tableData[] = ['52W High', $stockData['fifty_two_week_high'] ? '$' . number_format($stockData['fifty_two_week_high'], 2) : 'N/A'];
                $tableData[] = ['52W Low', $stockData['fifty_two_week_low'] ? '$' . number_format($stockData['fifty_two_week_low'], 2) : 'N/A'];
                $tableData[] = ['1W Performance', isset($stockData['price_performance_1week']) ? number_format($stockData['price_performance_1week'], 2) . '%' : 'N/A'];
                $tableData[] = ['1M Performance', isset($stockData['price_performance_1month']) ? number_format($stockData['price_performance_1month'], 2) . '%' : 'N/A'];
            }

            if ($analystData) {
                $tableData[] = ['---', '---'];
                $tableData[] = ['Price Target (Mean)', $analystData['price_target_mean'] ? '$' . number_format($analystData['price_target_mean'], 2) : 'N/A'];
                $tableData[] = ['Price Target (High)', $analystData['price_target_high'] ? '$' . number_format($analystData['price_target_high'], 2) : 'N/A'];
                $tableData[] = ['Price Target (Low)', $analystData['price_target_low'] ? '$' . number_format($analystData['price_target_low'], 2) : 'N/A'];
                $tableData[] = ['Recommendation', $analystData['recommendation'] ?? 'N/A'];
                $tableData[] = ['Analyst Count', $analystData['analyst_count'] ?? 'N/A'];
            }

            $this->table(['Field', 'Value'], $tableData);
        } else {
            $this->error("❌ Failed to scrape any data");
        }

        $this->newLine();
        $this->line("🏁 Complete scraping test finished!");
    }
}
