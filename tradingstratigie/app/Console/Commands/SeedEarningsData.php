<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\Earning;
use Carbon\Carbon;

class SeedEarningsData extends Command
{
    protected $signature = 'seed:earnings-data';
    protected $description = 'Seed sample earnings data for testing strategy endpoint';

    public function handle()
    {
        $this->info("🌱 Seeding sample earnings data for strategy testing...");
        
        // Clear existing earnings
        Earning::truncate();
        
        // Get some companies to add earnings data
        $companies = Company::take(20)->get();
        
        $earningsAdded = 0;
        
        foreach ($companies as $company) {
            // Add 1-3 future earnings dates for each company
            $earningsCount = rand(1, 2);
            
            for ($i = 0; $i < $earningsCount; $i++) {
                // Random date between 5-45 days from now
                $daysFromNow = rand(5, 45);
                $earningsDate = now()->addDays($daysFromNow);
                
                Earning::create([
                    'company_id' => $company->id,
                    'earnings_release_date' => $earningsDate,
                    'estimated_revenue' => rand(1000000000, 50000000000), // $1B to $50B
                    'actual_revenue' => null, // Future earnings
                    'api_data' => [
                        'source' => 'seeded_for_testing',
                        'created_at' => now()->toISOString()
                    ]
                ]);
                
                $earningsAdded++;
            }
        }
        
        $this->info("✅ Added {$earningsAdded} earnings records for {$companies->count()} companies");
        
        // Show distribution
        $this->line("\n📊 Earnings Distribution:");
        $ranges = [
            '7-14 days' => Earning::whereBetween('earnings_release_date', [now()->addDays(7), now()->addDays(14)])->count(),
            '14-21 days' => Earning::whereBetween('earnings_release_date', [now()->addDays(14), now()->addDays(21)])->count(),
            '21-30 days' => Earning::whereBetween('earnings_release_date', [now()->addDays(21), now()->addDays(30)])->count(),
            '30+ days' => Earning::where('earnings_release_date', '>', now()->addDays(30))->count(),
        ];
        
        foreach ($ranges as $range => $count) {
            $this->line("  {$range}: {$count} companies");
        }
        
        $this->info("\n🎯 Ready to test earnings strategy endpoint!");
    }
}
