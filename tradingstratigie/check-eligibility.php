<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Checking Company Eligibility Issues\n";
echo "====================================\n\n";

// Get sample companies
$companies = App\Models\Company::with(['financialData', 'earnings'])->take(10)->get();

foreach ($companies as $company) {
    echo "Company: {$company->symbol} - {$company->name}\n";
    
    if (!$company->financialData) {
        echo "  ❌ No financial data\n\n";
        continue;
    }
    
    $financial = $company->financialData;
    
    echo "  Price: $" . ($financial->current_stock_price ?? 'NULL') . "\n";
    echo "  Market Cap: $" . ($financial->market_cap ? number_format($financial->market_cap / 1000000000, 2) . 'B' : 'NULL') . "\n";
    echo "  Volume: " . ($financial->average_daily_volume ? number_format($financial->average_daily_volume) : 'NULL') . "\n";
    
    // Check eligibility criteria
    $issues = [];
    
    if (!$financial->market_cap || $financial->market_cap < 2000000000) {
        $issues[] = "Market cap < $2B (" . ($financial->market_cap ? '$' . number_format($financial->market_cap / 1000000000, 2) . 'B' : 'NULL') . ")";
    }
    
    if (!$financial->average_daily_volume || $financial->average_daily_volume < 1000000) {
        $issues[] = "Volume < 1M (" . ($financial->average_daily_volume ? number_format($financial->average_daily_volume) : 'NULL') . ")";
    }
    
    // Check earnings
    $nextEarning = $company->earnings()
        ->where('earnings_release_date', '>', now())
        ->orderBy('earnings_release_date')
        ->first();
    
    if (!$nextEarning) {
        $issues[] = "No upcoming earnings date";
        echo "  Earnings: ❌ None scheduled\n";
    } else {
        $daysUntil = now()->diffInDays($nextEarning->earnings_release_date);
        echo "  Earnings: " . $nextEarning->earnings_release_date . " ({$daysUntil} days)\n";
        
        if ($daysUntil < 1 || $daysUntil > 45) {
            $issues[] = "Earnings not in 1-45 day window ({$daysUntil} days)";
        }
    }
    
    if (empty($issues)) {
        echo "  ✅ ELIGIBLE\n";
    } else {
        echo "  ❌ NOT ELIGIBLE:\n";
        foreach ($issues as $issue) {
            echo "     - {$issue}\n";
        }
    }
    
    echo "\n";
}

// Summary
echo "\n====================================\n";
echo "Summary Statistics:\n";
echo "====================================\n";

$totalCompanies = App\Models\Company::count();
$withFinancialData = App\Models\Company::has('financialData')->count();
$withEarnings = App\Models\Company::has('earnings')->count();
$withFutureEarnings = App\Models\Company::whereHas('earnings', function($q) {
    $q->where('earnings_release_date', '>', now());
})->count();

echo "Total Companies: {$totalCompanies}\n";
echo "With Financial Data: {$withFinancialData}\n";
echo "With Earnings Records: {$withEarnings}\n";
echo "With Future Earnings: {$withFutureEarnings}\n";

echo "\nChecking specific criteria:\n";

$largeMarketCap = App\Models\Company::whereHas('financialData', function($q) {
    $q->where('market_cap', '>=', 2000000000);
})->count();

$highVolume = App\Models\Company::whereHas('financialData', function($q) {
    $q->where('average_daily_volume', '>=', 1000000);
})->count();

echo "Market Cap >= $2B: {$largeMarketCap}\n";
echo "Volume >= 1M: {$highVolume}\n";
