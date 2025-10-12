<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Debugging PYPL Price Issue\n";
echo "===========================\n\n";

// Check database
$pypl = App\Models\Company::where('symbol', 'PYPL')->with('financialData')->first();

if ($pypl && $pypl->financialData) {
    echo "Database Price: $" . $pypl->financialData->current_stock_price . "\n";
    echo "Updated: " . $pypl->financialData->updated_at . "\n\n";
}

// Fetch real price from Yahoo Finance
echo "Fetching real price from Yahoo Finance...\n\n";

$url = "https://query1.finance.yahoo.com/v8/finance/chart/PYPL";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    
    echo "Raw Yahoo Finance Response:\n";
    echo "===========================\n";
    
    if (isset($data['chart']['result'][0]['meta'])) {
        $meta = $data['chart']['result'][0]['meta'];
        
        echo "Symbol: " . ($meta['symbol'] ?? 'N/A') . "\n";
        echo "Currency: " . ($meta['currency'] ?? 'N/A') . "\n";
        echo "Regular Market Price: $" . ($meta['regularMarketPrice'] ?? 'N/A') . "\n";
        echo "Previous Close: $" . ($meta['previousClose'] ?? 'N/A') . "\n";
        echo "Chart Previous Close: $" . ($meta['chartPreviousClose'] ?? 'N/A') . "\n";
        
        echo "\nAll Meta Fields:\n";
        print_r($meta);
        
        // Check if there's a price mismatch
        $regularPrice = $meta['regularMarketPrice'] ?? 0;
        $dbPrice = $pypl->financialData->current_stock_price ?? 0;
        
        echo "\n\nComparison:\n";
        echo "Yahoo Finance Price: $" . number_format($regularPrice, 2) . "\n";
        echo "Database Price: $" . number_format($dbPrice, 2) . "\n";
        
        if (abs($regularPrice - $dbPrice) > 10) {
            echo "\n❌ MAJOR PRICE MISMATCH!\n";
            echo "Difference: $" . number_format(abs($regularPrice - $dbPrice), 2) . "\n";
        }
    } else {
        echo "❌ No meta data in response\n";
        echo "Full response:\n";
        print_r($data);
    }
} else {
    echo "❌ Failed to fetch from Yahoo Finance (HTTP {$httpCode})\n";
}
