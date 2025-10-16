<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing New Scraper with Finnhub + Yahoo\n";
echo "=========================================\n\n";

// Get AAPL company
$company = App\Models\Company::where('symbol', 'AAPL')->first();

if (!$company) {
    echo "❌ AAPL not found in database\n";
    exit(1);
}

echo "Testing scraper for: {$company->symbol} - {$company->name}\n\n";

// Create scraper service
$finnhubService = new \App\Services\FinnhubService();
$yahooService = new \App\Services\YahooFinanceService();
$scraper = new \App\Services\DataScrapingService($finnhubService, $yahooService);

// Run scraper
try {
    $scraper->processCompany($company);
    echo "✅ Scraping completed successfully!\n\n";
} catch (\Exception $e) {
    echo "❌ Scraping failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check what was saved
$company->load(['financialData', 'earnings']);

echo "Results:\n";
echo "========\n\n";

if ($company->financialData) {
    $f = $company->financialData;
    echo "Financial Data:\n";
    echo "  Price: $" . $f->current_stock_price . "\n";
    echo "  Market Cap: " . ($f->market_cap ? '$' . number_format($f->market_cap / 1000000000, 2) . 'B' : 'NULL') . "\n";
    echo "  Volume: " . number_format($f->average_daily_volume) . "\n";
    echo "  Performance 1W: " . ($f->price_performance_1week ? number_format($f->price_performance_1week, 2) . '%' : 'NULL') . "\n";
    echo "  Performance 1M: " . ($f->price_performance_1month ? number_format($f->price_performance_1month, 2) . '%' : 'NULL') . "\n";
    echo "  Fair Value: " . ($f->fair_value_estimate ? '$' . $f->fair_value_estimate : 'NULL') . "\n";
    echo "  Last Scraped: " . $f->last_scraped_at . "\n";
} else {
    echo "❌ No financial data saved\n";
}

echo "\n";

if ($company->earnings->count() > 0) {
    echo "Earnings Data:\n";
    foreach ($company->earnings as $earning) {
        echo "  Date: " . $earning->earnings_release_date . "\n";
        echo "  Estimated Revenue: " . ($earning->estimated_revenue ? '$' . number_format($earning->estimated_revenue) : 'NULL') . "\n";
    }
} else {
    echo "❌ No earnings data saved\n";
}

echo "\n✅ Test completed!\n";
