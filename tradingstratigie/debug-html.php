<?php

$symbol = 'AAPL';
$url = "https://finance.yahoo.com/quote/{$symbol}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$html = curl_exec($ch);
curl_close($ch);

// Find all fin-streamer tags with regularMarketPrice
preg_match_all('/<fin-streamer[^>]*data-field="regularMarketPrice"[^>]*>/', $html, $matches);

echo "Found fin-streamer tags for regularMarketPrice:\n";
echo "================================================\n\n";

foreach ($matches[0] as $match) {
    echo $match . "\n\n";
}

// Also check for data-value
preg_match_all('/<fin-streamer[^>]*data-field="regularMarketPrice"[^>]*data-value="([^"]+)"[^>]*>([^<]*)</', $html, $matches2);

echo "\nExtracted values:\n";
echo "=================\n";
if (isset($matches2[1])) {
    foreach ($matches2[1] as $idx => $value) {
        echo "data-value: " . $value . " | display: " . ($matches2[2][$idx] ?? 'N/A') . "\n";
    }
}
