<?php

// Test what data Yahoo Finance API actually returns

$symbol = 'AAPL';

echo "Testing Yahoo Finance API for {$symbol}\n";
echo "==========================================\n\n";

// Test 1: Quote endpoint
echo "1. Quote Endpoint (v8/finance/chart)\n";
echo "-------------------------------------\n";
$url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['chart']['result'][0]['meta'])) {
    $meta = $data['chart']['result'][0]['meta'];
    echo "Available fields in meta:\n";
    foreach ($meta as $key => $value) {
        if (!is_array($value)) {
            echo "  - {$key}: " . (is_numeric($value) ? number_format($value, 2) : $value) . "\n";
        }
    }
}

echo "\n\n2. Statistics Endpoint (v10/finance/quoteSummary)\n";
echo "---------------------------------------------------\n";
$url2 = "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$symbol}?modules=summaryDetail,price,defaultKeyStatistics";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response2 = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";

if ($httpCode === 200) {
    $data2 = json_decode($response2, true);
    
    if (isset($data2['quoteSummary']['result'][0])) {
        $result = $data2['quoteSummary']['result'][0];
        
        // Check for market cap in different places
        if (isset($result['summaryDetail']['marketCap']['raw'])) {
            echo "Market Cap (summaryDetail): $" . number_format($result['summaryDetail']['marketCap']['raw'] / 1000000000, 2) . "B\n";
        }
        
        if (isset($result['price']['marketCap']['raw'])) {
            echo "Market Cap (price): $" . number_format($result['price']['marketCap']['raw'] / 1000000000, 2) . "B\n";
        }
        
        if (isset($result['defaultKeyStatistics']['enterpriseValue']['raw'])) {
            echo "Enterprise Value: $" . number_format($result['defaultKeyStatistics']['enterpriseValue']['raw'] / 1000000000, 2) . "B\n";
        }
    }
} else {
    echo "Failed! Response:\n";
    echo substr($response2, 0, 500) . "\n";
}

echo "\n\n3. Alternative: Calculate from shares outstanding\n";
echo "---------------------------------------------------\n";

// Try to get shares outstanding
$url3 = "https://query1.finance.yahoo.com/v10/finance/quoteSummary/{$symbol}?modules=defaultKeyStatistics";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url3);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');

$response3 = curl_exec($ch);
$httpCode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode3}\n";

if ($httpCode3 === 200) {
    $data3 = json_decode($response3, true);
    if (isset($data3['quoteSummary']['result'][0]['defaultKeyStatistics']['sharesOutstanding']['raw'])) {
        $shares = $data3['quoteSummary']['result'][0]['defaultKeyStatistics']['sharesOutstanding']['raw'];
        $price = $meta['regularMarketPrice'];
        $calculatedMarketCap = $shares * $price;
        
        echo "Shares Outstanding: " . number_format($shares) . "\n";
        echo "Current Price: $" . number_format($price, 2) . "\n";
        echo "Calculated Market Cap: $" . number_format($calculatedMarketCap / 1000000000, 2) . "B\n";
    }
} else {
    echo "Failed!\n";
}
