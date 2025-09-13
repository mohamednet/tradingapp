<?php

// Simple API test script
$baseUrl = 'http://127.0.0.1:8000/api';

function testEndpoint($url, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

echo "🧪 TESTING API ENDPOINTS\n\n";

// Test 1: Get single company
echo "1. Testing single company (AAPL):\n";
$result = testEndpoint($baseUrl . '/companies/AAPL');
echo "Status: " . $result['status'] . "\n";
if ($result['response']['success']) {
    $data = $result['response']['data'];
    echo "✅ Company: {$data['symbol']} - {$data['name']}\n";
    echo "✅ Price: $" . number_format($data['financial_data']['current_stock_price'], 2) . "\n";
    echo "✅ Market Cap: {$data['financial_data']['market_cap_formatted']}\n";
} else {
    echo "❌ Error: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 2: Get multiple companies
echo "2. Testing multiple companies (AAPL,MSFT,GOOGL):\n";
$result = testEndpoint($baseUrl . '/companies/symbols', 'POST', ['symbols' => 'AAPL,MSFT,GOOGL']);
echo "Status: " . $result['status'] . "\n";
if ($result['response']['success']) {
    echo "✅ Found " . count($result['response']['data']) . " companies\n";
    foreach ($result['response']['data'] as $company) {
        echo "  - {$company['symbol']}: $" . number_format($company['financial_data']['current_stock_price'], 2) . "\n";
    }
} else {
    echo "❌ Error: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 3: Market summary
echo "3. Testing market summary:\n";
$result = testEndpoint($baseUrl . '/companies/market-summary');
echo "Status: " . $result['status'] . "\n";
if ($result['response']['success']) {
    $data = $result['response']['data'];
    echo "✅ Total Companies: {$data['total_companies']}\n";
    echo "✅ Financial Coverage: {$data['financial_data_coverage']}%\n";
    echo "✅ Total Market Cap: {$data['total_market_cap_formatted']}\n";
} else {
    echo "❌ Error: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}

echo "\n";

// Test 4: Top performers
echo "4. Testing top performers:\n";
$result = testEndpoint($baseUrl . '/companies/top-performers?limit=5');
echo "Status: " . $result['status'] . "\n";
if ($result['response']['success']) {
    echo "✅ Top 5 performers:\n";
    foreach ($result['response']['data'] as $company) {
        $perf = $company['financial_data']['price_performance_1week'];
        echo "  - {$company['symbol']}: " . ($perf > 0 ? '+' : '') . number_format($perf, 2) . "%\n";
    }
} else {
    echo "❌ Error: " . ($result['response']['message'] ?? 'Unknown error') . "\n";
}

echo "\n✅ API TESTING COMPLETED!\n";
