<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataScrapingService;
use App\Models\Company;
use App\Models\CompanyFinancialData;
use App\Models\Earning;
use App\Models\ScrapingLog;

class FinalDatabaseTest extends Command
{
    protected $signature = 'test:final-database';
    protected $description = 'Final comprehensive test to verify all database fields are populated';

    public function handle()
    {
        $this->info("🧪 FINAL DATABASE VERIFICATION TEST");
        $this->newLine();

        // Clear existing data for clean test
        $this->line("🗑️ Clearing existing test data...");
        CompanyFinancialData::truncate();
        Earning::truncate();
        ScrapingLog::truncate();

        // Ensure we have test companies
        $testCompanies = ['AAPL', 'MSFT', 'GOOGL'];
        foreach ($testCompanies as $symbol) {
            Company::firstOrCreate(['symbol' => $symbol], ['name' => $symbol . ' Inc']);
        }

        $this->info("✅ Test setup complete");
        $this->newLine();

        // Run scraping service
        $this->line("🚀 Running DataScrapingService...");
        $scrapingService = app(DataScrapingService::class);
        
        $results = $scrapingService->processAllCompanies();
        
        $this->info("📊 Scraping Results:");
        $this->line("Total companies: " . $results['total_companies']);
        $this->line("Successful: " . $results['successful_companies']);
        $this->line("Failed: " . $results['failed_companies']);
        
        if (!empty($results['errors'])) {
            $this->error("Errors encountered:");
            foreach ($results['errors'] as $error) {
                $this->line("  - {$error['company']}: {$error['error']}");
            }
        }
        
        $this->newLine();

        // Verify database contents
        $this->info("🔍 DATABASE VERIFICATION:");
        $this->newLine();

        // Check companies table
        $companiesCount = Company::count();
        $this->line("📋 Companies table: {$companiesCount} records");

        // Check earnings table
        $earningsCount = Earning::count();
        $this->line("💰 Earnings table: {$earningsCount} records");
        
        if ($earningsCount > 0) {
            $sampleEarning = Earning::with('company')->first();
            $this->line("  Sample: {$sampleEarning->company->symbol} - {$sampleEarning->earnings_release_date}");
        }

        // Check financial data table - THIS IS THE CRITICAL TEST
        $financialCount = CompanyFinancialData::count();
        $this->line("📈 Financial data table: {$financialCount} records");
        
        if ($financialCount > 0) {
            $this->newLine();
            $this->info("🎯 DETAILED FIELD VERIFICATION:");
            
            $financialData = CompanyFinancialData::with('company')->get();
            
            foreach ($financialData as $data) {
                $this->line("Company: {$data->company->symbol}");
                
                $fields = [
                    'current_stock_price' => $data->current_stock_price,
                    'market_cap' => $data->market_cap,
                    'average_daily_volume' => $data->average_daily_volume,
                    'price_performance_1week' => $data->price_performance_1week,
                    'price_performance_1month' => $data->price_performance_1month,
                    'fair_value_estimate' => $data->fair_value_estimate,
                    'last_scraped_at' => $data->last_scraped_at,
                ];
                
                $populatedFields = 0;
                $totalFields = count($fields);
                
                foreach ($fields as $fieldName => $value) {
                    $status = $value !== null ? '✅' : '❌';
                    $displayValue = $value !== null ? 
                        (is_numeric($value) ? number_format($value, 2) : $value) : 'NULL';
                    $this->line("  {$status} {$fieldName}: {$displayValue}");
                    
                    if ($value !== null) $populatedFields++;
                }
                
                $percentage = round(($populatedFields / $totalFields) * 100, 1);
                $this->line("  📊 Coverage: {$populatedFields}/{$totalFields} fields ({$percentage}%)");
                $this->newLine();
            }
        }

        // Check scraping logs
        $logsCount = ScrapingLog::count();
        $this->line("📝 Scraping logs: {$logsCount} records");
        
        if ($logsCount > 0) {
            $successLogs = ScrapingLog::where('status', 'success')->count();
            $errorLogs = ScrapingLog::where('status', 'error')->count();
            $this->line("  ✅ Success: {$successLogs}");
            $this->line("  ❌ Errors: {$errorLogs}");
        }

        $this->newLine();
        $this->info("🏁 FINAL DATABASE TEST COMPLETED!");
    }
}
