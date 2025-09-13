<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YahooFinanceService;
use App\Services\YahooScrapingService;

class DebugPerformance extends Command
{
    protected $signature = 'debug:performance {symbol=AAPL}';
    protected $description = 'Debug price performance calculation';

    public function handle()
    {
        $symbol = strtoupper($this->argument('symbol'));
        $this->info("🔍 Debugging price performance for: {$symbol}");
        
        // Get current price
        $scrapingService = new YahooScrapingService();
        $stockData = $scrapingService->scrapeStockData($symbol);
        $currentPrice = $stockData['current_price'] ?? null;
        
        $this->line("Current Price: " . ($currentPrice ? '$' . $currentPrice : 'N/A'));
        
        // Get historical data
        $yahooService = new YahooFinanceService();
        $historicalData = $yahooService->getHistoricalData($symbol, 30);
        
        $this->line("Historical data count: " . (is_array($historicalData) ? count($historicalData) : 'null'));
        
        if ($historicalData && is_array($historicalData)) {
            $this->line("First 3 historical prices:");
            for ($i = 0; $i < min(3, count($historicalData)); $i++) {
                $this->line("  [{$i}] Date: {$historicalData[$i]['date']}, Close: {$historicalData[$i]['close']}");
            }
            
            if (count($historicalData) >= 7) {
                $weekAgoPrice = $historicalData[6]['close'];
                $weekPerf = round((($currentPrice - $weekAgoPrice) / $weekAgoPrice) * 100, 2);
                $this->line("Week ago price (index 6): $weekAgoPrice");
                $this->line("1W Performance: {$weekPerf}%");
            }
            
            $oldestPrice = end($historicalData)['close'];
            $monthPerf = round((($currentPrice - $oldestPrice) / $oldestPrice) * 100, 2);
            $this->line("Oldest price: $oldestPrice");
            $this->line("1M Performance: {$monthPerf}%");
        }
        
        // Test the actual method
        $performance = $scrapingService->calculatePricePerformance($symbol, $currentPrice);
        $this->line("Method result: " . json_encode($performance));
    }
}
