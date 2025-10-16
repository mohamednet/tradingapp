<?php

// Test scraping estimated revenue from Yahoo Finance

$symbol = 'AAPL';
$url = "https://finance.yahoo.com/calendar/earnings?symbol={$symbol}";

echo "Testing Yahoo Finance Earnings Page Scraping\n";
echo "=============================================\n\n";
echo "URL: {$url}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "HTML Length: " . strlen($html) . " bytes\n\n";

// Save for inspection
file_put_contents('yahoo-earnings-page.html', $html);
echo "HTML saved to yahoo-earnings-page.html\n\n";

// Try to find earnings data in JSON
echo "1. Looking for embedded JSON data:\n";
echo "-----------------------------------\n";

if (preg_match('/root\.App\.main\s*=\s*({.+?});/s', $html, $match)) {
    echo "✅ Found embedded JSON\n";
    $jsonData = json_decode($match[1], true);
    
    if ($jsonData) {
        $jsonStr = json_encode($jsonData);
        
        // Look for revenue estimate
        if (preg_match('/"revenueEstimate"[^}]*"raw":([0-9.]+)/', $jsonStr, $revMatch)) {
            echo "Revenue Estimate: $" . number_format($revMatch[1]) . "\n";
        }
        
        // Look for EPS estimate
        if (preg_match('/"epsEstimate"[^}]*"raw":([0-9.]+)/', $jsonStr, $epsMatch)) {
            echo "EPS Estimate: $" . $epsMatch[1] . "\n";
        }
    }
} else {
    echo "❌ No embedded JSON found\n";
}

// Try alternative: Look for table data
echo "\n2. Looking for earnings table:\n";
echo "-------------------------------\n";

// Look for earnings estimates in table format
if (preg_match_all('/<td[^>]*>([^<]+)<\/td>/i', $html, $matches)) {
    echo "Found " . count($matches[1]) . " table cells\n";
    
    // Look for revenue-like numbers (in billions/millions)
    foreach ($matches[1] as $cell) {
        $cell = trim($cell);
        if (preg_match('/^\$?[\d,]+\.?\d*[BMK]?$/i', $cell) && strlen($cell) > 3) {
            echo "  Potential value: {$cell}\n";
        }
    }
}

// Try to find analyst recommendations
echo "\n3. Looking for analyst data:\n";
echo "-----------------------------\n";

if (preg_match('/"recommendationMean"[^}]*"raw":([0-9.]+)/', $html, $recMatch)) {
    echo "Recommendation Mean: " . $recMatch[1] . " (1=Strong Buy, 5=Sell)\n";
}

if (preg_match('/"numberOfAnalystOpinions"[^}]*"raw":([0-9]+)/', $html, $analystMatch)) {
    echo "Number of Analysts: " . $analystMatch[1] . "\n";
}

echo "\n✅ Check yahoo-earnings-page.html for full content\n";
