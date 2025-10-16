<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Testing HTML Scraping for AAPL\n";
echo "==============================\n\n";

$scrapingService = new \App\Services\YahooScrapingService();

// Test scraping stock data
echo "1. Scraping Stock Data...\n";
$stockData = $scrapingService->scrapeStockData('AAPL');

echo "Results:\n";
print_r($stockData);

echo "\n\n2. Scraping Analyst Data...\n";
$analystData = $scrapingService->scrapeAnalystData('AAPL');

echo "Results:\n";
print_r($analystData);

echo "\n\n3. Calculating Price Performance...\n";
if ($stockData && $stockData['current_price']) {
    $performance = $scrapingService->calculatePricePerformance('AAPL', $stockData['current_price']);
    echo "Results:\n";
    print_r($performance);
}
