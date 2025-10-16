<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$symbol = 'AAPL';

echo "Testing Finnhub getAllCompanyData() for {$symbol}\n";
echo "===================================================\n\n";

$finnhubService = new \App\Services\FinnhubService();

if (!$finnhubService->isConfigured()) {
    echo "❌ Finnhub API key not configured!\n";
    echo "Add FINNHUB_API_KEY to your .env file\n";
    exit(1);
}

$data = $finnhubService->getAllCompanyData($symbol);

echo "Response:\n";
echo json_encode($data, JSON_PRETTY_PRINT);

echo "\n\n===========================================\n";
echo "Extracted Key Data:\n";
echo "===========================================\n";

if ($data['profile']) {
    echo "Company Name: " . ($data['profile']['name'] ?? 'N/A') . "\n";
    echo "Market Cap: $" . number_format(($data['profile']['marketCapitalization'] ?? 0) * 1000000) . "\n";
    echo "Industry: " . ($data['profile']['finnhubIndustry'] ?? 'N/A') . "\n";
    echo "Country: " . ($data['profile']['country'] ?? 'N/A') . "\n";
}

if ($data['quote']) {
    echo "\nCurrent Price: $" . ($data['quote']['c'] ?? 'N/A') . "\n";
    echo "High: $" . ($data['quote']['h'] ?? 'N/A') . "\n";
    echo "Low: $" . ($data['quote']['l'] ?? 'N/A') . "\n";
    echo "Previous Close: $" . ($data['quote']['pc'] ?? 'N/A') . "\n";
}

if ($data['metrics'] && isset($data['metrics']['metric'])) {
    $m = $data['metrics']['metric'];
    echo "\n10-Day Avg Volume: " . number_format($m['10DayAverageTradingVolume'] ?? 0) . "\n";
    echo "52-Week High: $" . ($m['52WeekHigh'] ?? 'N/A') . "\n";
    echo "52-Week Low: $" . ($m['52WeekLow'] ?? 'N/A') . "\n";
    echo "PE Ratio: " . ($m['peBasicExclExtraTTM'] ?? 'N/A') . "\n";
    echo "Beta: " . ($m['beta'] ?? 'N/A') . "\n";
}

if ($data['earnings'] && isset($data['earnings']['earningsCalendar'][0])) {
    $e = $data['earnings']['earningsCalendar'][0];
    echo "\nNext Earnings Date: " . ($e['date'] ?? 'N/A') . "\n";
    echo "EPS Estimate: $" . ($e['epsEstimate'] ?? 'N/A') . "\n";
}

if (!empty($data['errors'])) {
    echo "\n\n⚠️  Errors:\n";
    foreach ($data['errors'] as $error) {
        echo "  - {$error}\n";
    }
}
