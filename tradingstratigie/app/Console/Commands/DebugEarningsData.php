<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Earning;
use App\Services\FinnhubService;
use Illuminate\Console\Command;

class DebugEarningsData extends Command
{
    protected $signature = 'debug:earnings {symbol=TSLA}';
    protected $description = 'Debug earnings data for a specific company';

    public function handle(FinnhubService $finnhubService)
    {
        $symbol = $this->argument('symbol');
        
        $this->info("Fetching data for {$symbol}...");
        $this->newLine();
        
        // Check what Finnhub returns
        $this->info("=== FINNHUB API RESPONSE ===");
        $finnhubData = $finnhubService->getEarnings($symbol);
        dump($finnhubData);
        
        $this->newLine();
        
        // Check what's in database
        $this->info("=== DATABASE RECORDS ===");
        $company = Company::where('symbol', $symbol)->first();
        
        if (!$company) {
            $this->error("Company {$symbol} not found in database!");
            return 1;
        }
        
        $earnings = Earning::where('company_id', $company->id)
            ->orderBy('earnings_release_date', 'desc')
            ->get();
            
        $this->table(
            ['ID', 'Earnings Date', 'Est Revenue', 'Actual Revenue', 'Created At'],
            $earnings->map(fn($e) => [
                $e->id,
                $e->earnings_release_date?->format('Y-m-d'),
                $e->estimated_revenue,
                $e->actual_revenue,
                $e->created_at->format('Y-m-d H:i:s')
            ])
        );
        
        $this->newLine();
        $this->info("Total earnings records: " . $earnings->count());
        
        return 0;
    }
}
