<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Earning;
use App\Models\Company;

class CheckEarnings extends Command
{
    protected $signature = 'check:earnings';
    protected $description = 'Check which companies have earnings data';

    public function handle()
    {
        $this->info("📊 EARNINGS DATA ANALYSIS");
        $this->newLine();

        $totalCompanies = Company::count();
        $totalEarnings = Earning::count();
        $companiesWithEarnings = Earning::distinct('company_id')->count();

        $this->line("Total companies: {$totalCompanies}");
        $this->line("Total earnings records: {$totalEarnings}");
        $this->line("Companies with earnings: {$companiesWithEarnings}");
        $this->newLine();

        $this->info("Companies WITH earnings data:");
        $earnings = Earning::with('company')->get();
        
        $companiesWithData = [];
        foreach ($earnings as $earning) {
            $symbol = $earning->company->symbol;
            if (!isset($companiesWithData[$symbol])) {
                $companiesWithData[$symbol] = 0;
            }
            $companiesWithData[$symbol]++;
        }

        foreach ($companiesWithData as $symbol => $count) {
            $this->line("✅ {$symbol}: {$count} earnings records");
        }

        $this->newLine();
        $this->info("Companies WITHOUT earnings data:");
        $companiesWithoutEarnings = Company::whereNotIn('id', 
            Earning::distinct('company_id')->pluck('company_id')
        )->get();

        foreach ($companiesWithoutEarnings as $company) {
            $this->line("❌ {$company->symbol}: No earnings data");
        }

        $percentage = round(($companiesWithEarnings / $totalCompanies) * 100, 1);
        $this->newLine();
        $this->info("📈 Earnings coverage: {$companiesWithEarnings}/{$totalCompanies} companies ({$percentage}%)");
    }
}
