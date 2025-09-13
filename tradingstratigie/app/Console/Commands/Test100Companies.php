<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataScrapingService;
use App\Models\Company;
use App\Models\CompanyFinancialData;
use App\Models\Earning;
use App\Models\ScrapingLog;

class Test100Companies extends Command
{
    protected $signature = 'test:100-companies';
    protected $description = 'Test data scraping with 100 companies to verify scalability';

    public function handle()
    {
        $this->info("🚀 TESTING 100 COMPANIES - SCALABILITY TEST");
        $this->newLine();

        $startTime = microtime(true);

        // Clear existing data for clean test
        $this->line("🗑️ Clearing existing data...");
        CompanyFinancialData::truncate();
        Earning::truncate();
        ScrapingLog::truncate();

        $totalCompanies = Company::count();
        $this->info("📊 Total companies to process: {$totalCompanies}");
        $this->newLine();

        // Run scraping service
        $this->line("🔄 Starting data scraping service...");
        $scrapingService = app(DataScrapingService::class);
        
        $results = $scrapingService->processAllCompanies();
        
        $totalTime = microtime(true) - $startTime;
        
        $this->newLine();
        $this->info("⏱️ PERFORMANCE METRICS:");
        $this->line("Total execution time: " . round($totalTime, 2) . " seconds");
        $this->line("Average time per company: " . round($totalTime / $totalCompanies, 2) . " seconds");
        $this->line("Companies per minute: " . round(($totalCompanies / $totalTime) * 60, 1));
        
        $this->newLine();
        $this->info("📊 SCRAPING RESULTS:");
        $this->line("Total companies: " . $results['total_companies']);
        $this->line("Successful: " . $results['successful_companies']);
        $this->line("Failed: " . $results['failed_companies']);
        $this->line("Success rate: " . round(($results['successful_companies'] / $results['total_companies']) * 100, 1) . "%");
        
        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error("❌ ERRORS ENCOUNTERED:");
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->line("  - {$error['company']}: {$error['error']}");
            }
            if (count($results['errors']) > 10) {
                $this->line("  ... and " . (count($results['errors']) - 10) . " more errors");
            }
        }
        
        $this->newLine();
        $this->info("🔍 DATABASE VERIFICATION:");

        // Check financial data
        $financialCount = CompanyFinancialData::count();
        $this->line("💰 Financial data records: {$financialCount}");
        
        if ($financialCount > 0) {
            $completeRecords = CompanyFinancialData::whereNotNull('current_stock_price')
                ->whereNotNull('market_cap')
                ->whereNotNull('average_daily_volume')
                ->whereNotNull('price_performance_1week')
                ->whereNotNull('price_performance_1month')
                ->whereNotNull('fair_value_estimate')
                ->count();
            
            $partialRecords = $financialCount - $completeRecords;
            
            $this->line("  ✅ Complete records (all 6 fields): {$completeRecords}");
            $this->line("  ⚠️ Partial records (missing some fields): {$partialRecords}");
            $this->line("  📈 Complete data rate: " . round(($completeRecords / $financialCount) * 100, 1) . "%");
        }

        // Check earnings data
        $earningsCount = Earning::count();
        $companiesWithEarnings = Earning::distinct('company_id')->count();
        $this->line("📅 Earnings records: {$earningsCount}");
        $this->line("📊 Companies with earnings: {$companiesWithEarnings}");
        $this->line("📈 Earnings coverage: " . round(($companiesWithEarnings / $totalCompanies) * 100, 1) . "%");

        // Check scraping logs
        $logsCount = ScrapingLog::count();
        $successLogs = ScrapingLog::where('status', 'success')->count();
        $errorLogs = ScrapingLog::where('status', 'error')->count();
        
        $this->line("📝 Scraping logs: {$logsCount}");
        $this->line("  ✅ Success: {$successLogs}");
        $this->line("  ❌ Errors: {$errorLogs}");

        // Sample data verification
        $this->newLine();
        $this->info("🎯 SAMPLE DATA VERIFICATION:");
        
        $sampleData = CompanyFinancialData::with('company')
            ->whereNotNull('current_stock_price')
            ->take(5)
            ->get();
            
        foreach ($sampleData as $data) {
            $this->line("📊 {$data->company->symbol}:");
            $this->line("  Price: $" . number_format($data->current_stock_price, 2));
            $this->line("  Market Cap: $" . number_format($data->market_cap));
            $this->line("  1W Perf: " . ($data->price_performance_1week ? number_format($data->price_performance_1week, 2) . "%" : 'N/A'));
            $this->line("  Target: $" . ($data->fair_value_estimate ? number_format($data->fair_value_estimate, 2) : 'N/A'));
        }

        $this->newLine();
        $this->info("🏁 100-COMPANY SCALE TEST COMPLETED!");
        $this->line("System processed {$totalCompanies} companies in " . round($totalTime/60, 1) . " minutes");
    }
}
