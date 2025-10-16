<?php

$symbol = 'AAPL';
$url = "https://finance.yahoo.com/quote/{$symbol}";

echo "Testing What We Can Scrape from Yahoo Finance HTML\n";
echo "===================================================\n\n";

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

// Save HTML to file for inspection
file_put_contents('yahoo-html-sample.html', $html);
echo "HTML saved to yahoo-html-sample.html\n\n";

// Test 1: Look for fin-streamer tags
echo "1. Searching for fin-streamer tags:\n";
echo "------------------------------------\n";
preg_match_all('/<fin-streamer[^>]*data-field="([^"]+)"[^>]*data-value="([^"]*)"[^>]*>([^<]*)<\/fin-streamer>/', $html, $matches, PREG_SET_ORDER);

if (count($matches) > 0) {
    echo "Found " . count($matches) . " fin-streamer tags:\n\n";
    foreach (array_slice($matches, 0, 15) as $match) {
        echo sprintf("Field: %-30s | Value: %-15s | Display: %s\n", 
            $match[1], 
            $match[2], 
            trim($match[3])
        );
    }
} else {
    echo "❌ No fin-streamer tags found\n";
}

// Test 2: Look for specific data in table format
echo "\n\n2. Searching for data tables:\n";
echo "------------------------------\n";

// Market Cap
if (preg_match('/Market Cap[^>]*>([^<]+)</', $html, $match)) {
    echo "Market Cap: " . trim($match[1]) . "\n";
} else {
    echo "Market Cap: Not found\n";
}

// Volume
if (preg_match('/Volume[^>]*>([^<]+)</', $html, $match)) {
    echo "Volume: " . trim($match[1]) . "\n";
} else {
    echo "Volume: Not found\n";
}

// PE Ratio
if (preg_match('/PE Ratio[^>]*>([^<]+)</', $html, $match)) {
    echo "PE Ratio: " . trim($match[1]) . "\n";
} else {
    echo "PE Ratio: Not found\n";
}

// Test 3: Check if page has JavaScript-rendered content
echo "\n\n3. Checking page structure:\n";
echo "----------------------------\n";

if (strpos($html, 'fin-streamer') !== false) {
    echo "✅ Page contains fin-streamer tags (but may need JS to populate)\n";
} else {
    echo "❌ No fin-streamer tags in HTML\n";
}

if (strpos($html, 'root') !== false && strpos($html, 'react') !== false) {
    echo "⚠️  Page uses React (JavaScript required for data)\n";
} else {
    echo "✅ Page might have static HTML data\n";
}

// Test 4: Look for JSON data embedded in page
echo "\n\n4. Searching for embedded JSON data:\n";
echo "--------------------------------------\n";

if (preg_match('/root\.App\.main\s*=\s*({.+?});/s', $html, $match)) {
    echo "✅ Found embedded JSON data\n";
    $jsonData = json_decode($match[1], true);
    if ($jsonData) {
        echo "JSON successfully parsed\n";
        // Try to find price in JSON
        $jsonStr = json_encode($jsonData);
        if (preg_match('/"regularMarketPrice"[^}]*"raw":([0-9.]+)/', $jsonStr, $priceMatch)) {
            echo "Price found in JSON: $" . $priceMatch[1] . "\n";
        }
        if (preg_match('/"marketCap"[^}]*"raw":([0-9.]+)/', $jsonStr, $capMatch)) {
            echo "Market Cap found in JSON: " . number_format($capMatch[1]) . "\n";
        }
    }
} else {
    echo "❌ No embedded JSON data found\n";
}

echo "\n\n✅ Check yahoo-html-sample.html file for full HTML content\n";
