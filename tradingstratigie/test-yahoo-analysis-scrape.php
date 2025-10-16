<?php

// Test scraping from Yahoo Finance Analysis page

$symbol = 'AAPL';
$url = "https://finance.yahoo.com/quote/{$symbol}";

echo "Testing Yahoo Finance Analysis Page\n";
echo "====================================\n\n";
echo "URL: {$url}\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "HTML Length: " . strlen($html) . " bytes\n\n";

file_put_contents('yahoo-analysis-page.html', $html);

// Look for JSON data in the page
echo "Looking for embedded data:\n";
echo "--------------------------\n";

if (preg_match('/root\.App\.main\s*=\s*({.+?});/s', $html, $match)) {
    echo "✅ Found JSON data\n\n";
    $jsonData = json_decode($match[1], true);
    
    if ($jsonData) {
        // Navigate through the JSON structure
        $jsonStr = json_encode($jsonData, JSON_PRETTY_PRINT);
        
        // Save JSON for inspection
        file_put_contents('yahoo-analysis-data.json', $jsonStr);
        echo "JSON saved to yahoo-analysis-data.json\n\n";
        
        // Look for key data points
        echo "Extracted Data:\n";
        echo "===============\n";
        
        // Revenue estimate
        if (preg_match('/"revenueEstimate"[^}]*"avg"[^}]*"raw":([0-9.]+)/', $jsonStr, $match)) {
            echo "Revenue Estimate: $" . number_format($match[1]) . "\n";
        }
        
        // EPS estimates
        if (preg_match('/"epsEstimate"[^}]*"avg"[^}]*"raw":([0-9.]+)/', $jsonStr, $match)) {
            echo "EPS Estimate: $" . $match[1] . "\n";
        }
        
        // Recommendation
        if (preg_match('/"recommendationMean"[^}]*"raw":([0-9.]+)/', $jsonStr, $match)) {
            $rec = $match[1];
            echo "Recommendation Mean: {$rec} ";
            if ($rec <= 1.5) echo "(Strong Buy)\n";
            elseif ($rec <= 2.5) echo "(Buy)\n";
            elseif ($rec <= 3.5) echo "(Hold)\n";
            elseif ($rec <= 4.5) echo "(Sell)\n";
            else echo "(Strong Sell)\n";
        }
        
        // Number of analysts
        if (preg_match('/"numberOfAnalystOpinions"[^}]*"raw":([0-9]+)/', $jsonStr, $match)) {
            echo "Number of Analysts: " . $match[1] . "\n";
        }
        
        // Target price
        if (preg_match('/"targetMeanPrice"[^}]*"raw":([0-9.]+)/', $jsonStr, $match)) {
            echo "Target Mean Price: $" . $match[1] . "\n";
        }
        
        // Earnings trend (current quarter)
        if (preg_match('/"earningsTrend"[^}]*"current"[^}]*"revenueEstimate"[^}]*"avg"[^}]*"raw":([0-9.]+)/', $jsonStr, $match)) {
            echo "Current Quarter Revenue Estimate: $" . number_format($match[1]) . "\n";
        }
        
    } else {
        echo "❌ Failed to parse JSON\n";
    }
} else {
    echo "❌ No JSON data found\n";
}

echo "\n✅ Check yahoo-analysis-page.html and yahoo-analysis-data.json\n";
