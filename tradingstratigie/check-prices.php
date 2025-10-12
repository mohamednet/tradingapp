<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Stock Prices in Database vs Real Prices\n";
echo "=================================================\n\n";

// Get some companies with prices
$companies = App\Models\Company::with('financialData')
    ->whereHas('financialData')
    ->take(10)
    ->get();

foreach ($companies as $company) {
    $dbPrice = $company->financialData->current_stock_price ?? 'N/A';
    $updated = $company->financialData->updated_at ?? 'Never';
    
    echo sprintf(
        "%-6s | DB Price: $%-8s | Updated: %s\n",
        $company->symbol,
        $dbPrice,
        $updated
    );
}

echo "\n=================================================\n";
echo "To get real-time prices, let me fetch from Yahoo Finance...\n\n";

// Test fetching real price for one stock
$testSymbol = 'GOOGL';
$company = App\Models\Company::where('symbol', $testSymbol)->first();

if ($company) {
    echo "Fetching real price for {$testSymbol}...\n";
    
    try {
        $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$testSymbol}";
        
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
            
            if (isset($data['chart']['result'][0]['meta']['regularMarketPrice'])) {
                $realPrice = $data['chart']['result'][0]['meta']['regularMarketPrice'];
                $dbPrice = $company->financialData->current_stock_price ?? 0;
                
                echo "\n✅ Real-time price fetched successfully!\n";
                echo "   Symbol: {$testSymbol}\n";
                echo "   Real Price: $" . number_format($realPrice, 2) . "\n";
                echo "   DB Price: $" . number_format($dbPrice, 2) . "\n";
                echo "   Difference: $" . number_format(abs($realPrice - $dbPrice), 2) . "\n";
                
                if (abs($realPrice - $dbPrice) > 1) {
                    echo "   ⚠️  Prices are different! Database needs update.\n";
                } else {
                    echo "   ✅ Prices match!\n";
                }
            } else {
                echo "❌ Could not parse price from response\n";
                echo "Response structure: " . print_r($data, true) . "\n";
            }
        } else {
            echo "❌ Failed to fetch from Yahoo Finance (HTTP {$httpCode})\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
