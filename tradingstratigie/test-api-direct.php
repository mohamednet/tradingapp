<?php

$symbol = 'AAPL';
$url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}";

$response = file_get_contents($url);
$data = json_decode($response, true);

echo "Yahoo Finance API Response for {$symbol}\n";
echo "=========================================\n\n";

if (isset($data['chart']['result'][0]['meta'])) {
    $meta = $data['chart']['result'][0]['meta'];
    
    echo "All available fields:\n";
    echo "---------------------\n";
    foreach ($meta as $key => $value) {
        if (!is_array($value)) {
            echo sprintf("%-30s = %s\n", $key, $value);
        }
    }
}
