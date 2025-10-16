<?php

// Test Finnhub API to see what data we can get
// Get free API key from: https://finnhub.io/register

$apiKey = 'YOUR_API_KEY'; // Replace with actual key
$symbol = 'AAPL';

echo "Testing Finnhub API for {$symbol}\n";
echo "===================================\n\n";

// Test 1: Company Profile (includes market cap)
echo "1. Company Profile (Basic Data):\n";
echo "---------------------------------\n";
$url1 = "https://finnhub.io/api/v1/stock/profile2?symbol={$symbol}&token={$apiKey}";
$response1 = file_get_contents($url1);
$data1 = json_decode($response1, true);
print_r($data1);

echo "\n\n2. Quote (Real-time Price):\n";
echo "----------------------------\n";
$url2 = "https://finnhub.io/api/v1/quote?symbol={$symbol}&token={$apiKey}";
$response2 = file_get_contents($url2);
$data2 = json_decode($response2, true);
print_r($data2);

echo "\n\n3. Basic Financials (Metrics):\n";
echo "--------------------------------\n";
$url3 = "https://finnhub.io/api/v1/stock/metric?symbol={$symbol}&metric=all&token={$apiKey}";
$response3 = file_get_contents($url3);
$data3 = json_decode($response3, true);
if (isset($data3['metric'])) {
    echo "Available metrics:\n";
    foreach (array_slice($data3['metric'], 0, 20) as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
}

echo "\n\n4. Earnings Calendar:\n";
echo "----------------------\n";
$url4 = "https://finnhub.io/api/v1/calendar/earnings?symbol={$symbol}&token={$apiKey}";
$response4 = file_get_contents($url4);
$data4 = json_decode($response4, true);
print_r($data4);

echo "\n\n===========================================\n";
echo "Summary of What Finnhub Provides:\n";
echo "===========================================\n";
echo "✅ Market Cap: " . ($data1['marketCapitalization'] ?? 'N/A') . " million\n";
echo "✅ Current Price: $" . ($data2['c'] ?? 'N/A') . "\n";
echo "✅ Volume: " . (isset($data3['metric']['10DayAverageTradingVolume']) ? number_format($data3['metric']['10DayAverageTradingVolume']) : 'N/A') . "\n";
echo "✅ PE Ratio: " . ($data3['metric']['peBasicExclExtraTTM'] ?? 'N/A') . "\n";
echo "✅ 52W High: $" . ($data2['h'] ?? 'N/A') . "\n";
echo "✅ 52W Low: $" . ($data2['l'] ?? 'N/A') . "\n";
echo "✅ Earnings Date: " . ($data4['earningsCalendar'][0]['date'] ?? 'N/A') . "\n";
