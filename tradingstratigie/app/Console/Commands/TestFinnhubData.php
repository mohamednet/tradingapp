<?php

namespace App\Console\Commands;

use App\Services\FinnhubService;
use Illuminate\Console\Command;

class TestFinnhubData extends Command
{
    protected $signature = 'test:finnhub {symbol=TSLA}';
    protected $description = 'Test what data Finnhub API returns';

    public function handle(FinnhubService $finnhubService)
    {
        $symbol = $this->argument('symbol');
        
        if (!$finnhubService->isConfigured()) {
            $this->error('Finnhub API key is not configured!');
            $this->info('Set FINNHUB_API_KEY in your .env file');
            return 1;
        }
        
        $this->info("Testing Finnhub API for {$symbol}...");
        $this->newLine();
        
        // Test 1: Earnings Calendar
        $this->info("=== 1. EARNINGS CALENDAR (getEarnings) ===");
        $earnings = $finnhubService->getEarnings($symbol);
        $this->line(json_encode($earnings, JSON_PRETTY_PRINT));
        $this->newLine();
        
        // Test 2: Company Profile
        $this->info("=== 2. COMPANY PROFILE (getCompanyProfile) ===");
        $profile = $finnhubService->getCompanyProfile($symbol);
        $this->line(json_encode($profile, JSON_PRETTY_PRINT));
        $this->newLine();
        
        // Test 3: Earnings Estimates
        $this->info("=== 3. EARNINGS ESTIMATES (getEarningsEstimates) ===");
        $estimates = $finnhubService->getEarningsEstimates($symbol);
        $this->line(json_encode($estimates, JSON_PRETTY_PRINT));
        $this->newLine();
        
        // Summary
        $this->info("=== SUMMARY ===");
        $this->table(
            ['Endpoint', 'Status', 'Data Count'],
            [
                [
                    'Earnings Calendar',
                    $earnings ? '✓ Success' : '✗ Failed',
                    isset($earnings['earningsCalendar']) ? count($earnings['earningsCalendar']) : 0
                ],
                [
                    'Company Profile',
                    $profile ? '✓ Success' : '✗ Failed',
                    $profile ? count($profile) : 0
                ],
                [
                    'Earnings Estimates',
                    $estimates ? '✓ Success' : '✗ Failed',
                    is_array($estimates) ? count($estimates) : 0
                ],
            ]
        );
        
        return 0;
    }
}
