<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking What Data We Get From Yahoo Finance\n";
echo "=============================================\n\n";

$company = App\Models\Company::where('symbol', 'AAPL')->with('financialData')->first();

if (!$company || !$company->financialData) {
    echo "No data found for AAPL\n";
    exit;
}

$financial = $company->financialData;

echo "AAPL Financial Data:\n";
echo "-------------------\n";
echo "Current Price: $" . ($financial->current_stock_price ?? 'NULL') . "\n";
echo "Market Cap: " . ($financial->market_cap ?? 'NULL') . "\n";
echo "Volume: " . ($financial->average_daily_volume ?? 'NULL') . "\n";
echo "Price Performance 1W: " . ($financial->price_performance_1week ?? 'NULL') . "\n";
echo "Price Performance 1M: " . ($financial->price_performance_1month ?? 'NULL') . "\n";
echo "Fair Value Estimate: " . ($financial->fair_value_estimate ?? 'NULL') . "\n";
echo "Last Scraped: " . ($financial->last_scraped_at ?? 'NULL') . "\n";

echo "\n\nData Sources (what Yahoo returned):\n";
echo "====================================\n";

$dataSources = is_string($financial->data_sources) ? json_decode($financial->data_sources, true) : $financial->data_sources;

if ($dataSources) {
    echo "\n1. Stock Data:\n";
    print_r($dataSources['scraped_stock_data'] ?? 'NULL');
    
    echo "\n2. Analyst Data:\n";
    print_r($dataSources['scraped_analyst_data'] ?? 'NULL');
    
    echo "\n3. Performance Data:\n";
    print_r($dataSources['performance_data'] ?? 'NULL');
} else {
    echo "No data sources saved\n";
}
