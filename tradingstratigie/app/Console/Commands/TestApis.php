<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinnhubService;
use App\Services\YahooFinanceService;

class TestApis extends Command
{
    protected $signature = 'test:apis {symbol=AAPL}';
    protected $description = 'Test Finnhub and Yahoo Finance APIs to check response structure';

    public function handle()
    {
        $symbol = $this->argument('symbol');
        
        $this->info("🧪 Testing APIs for symbol: {$symbol}");
        $this->newLine();

        // Test Finnhub API
        $this->info("📊 Testing Finnhub API...");
        $finnhubService = new FinnhubService();
        
        $this->line("1. Testing getEarnings():");
        $earnings = $finnhubService->getEarnings($symbol);
        $this->line("Response: " . json_encode($earnings, JSON_PRETTY_PRINT));
        $this->newLine();

        $this->line("2. Testing getEarningsEstimates():");
        $estimates = $finnhubService->getEarningsEstimates($symbol);
        $this->line("Response: " . json_encode($estimates, JSON_PRETTY_PRINT));
        $this->newLine();

        $this->line("3. Testing getCompanyProfile():");
        $profile = $finnhubService->getCompanyProfile($symbol);
        $this->line("Response: " . json_encode($profile, JSON_PRETTY_PRINT));
        $this->newLine();

        // Test Yahoo Finance API
        $this->info("💰 Testing Yahoo Finance API...");
        $yahooService = new YahooFinanceService();

        $this->line("1. Testing getQuote():");
        $quote = $yahooService->getQuote($symbol);
        $this->line("Response: " . json_encode($quote, JSON_PRETTY_PRINT));
        $this->newLine();

        $this->line("2. Testing getStatistics():");
        $stats = $yahooService->getStatistics($symbol);
        $this->line("Response: " . json_encode($stats, JSON_PRETTY_PRINT));
        $this->newLine();

        $this->line("3. Testing getHistoricalData():");
        $historical = $yahooService->getHistoricalData($symbol, 30);
        $this->line("Response: " . json_encode($historical, JSON_PRETTY_PRINT));
        $this->newLine();

        $this->line("4. Testing getAnalystData():");
        $analyst = $yahooService->getAnalystData($symbol);
        $this->line("Response: " . json_encode($analyst, JSON_PRETTY_PRINT));
        $this->newLine();

        $this->info("✅ API testing completed!");
    }
}
