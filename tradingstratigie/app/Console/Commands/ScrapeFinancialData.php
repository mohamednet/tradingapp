<?php

namespace App\Console\Commands;

use App\Services\DataScrapingService;
use Illuminate\Console\Command;

class ScrapeFinancialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:financial-data {--company= : Specific company symbol to scrape}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape financial data from Finnhub and Yahoo Finance APIs for all companies';

    /**
     * Execute the console command.
     */
    public function handle(DataScrapingService $scrapingService)
    {
        $this->info('🚀 Starting financial data scraping...');
        
        $startTime = microtime(true);
        
        try {
            // Check if specific company requested
            $companySymbol = $this->option('company');
            
            if ($companySymbol) {
                $this->info("Scraping data for specific company: {$companySymbol}");
                $company = \App\Models\Company::where('symbol', $companySymbol)->first();
                
                if (!$company) {
                    $this->error("Company with symbol '{$companySymbol}' not found!");
                    return 1;
                }
                
                $scrapingService->processCompany($company);
                $this->info("✅ Successfully processed {$companySymbol}");
                
            } else {
                // Process all companies
                $this->info('Processing all companies...');
                $results = $scrapingService->processAllCompanies();
                
                // Display results
                $this->newLine();
                $this->info('📊 Scraping Results:');
                $this->table(
                    ['Metric', 'Count'],
                    [
                        ['Total Companies', $results['total_companies']],
                        ['Successful', $results['successful_companies']],
                        ['Failed', $results['failed_companies']],
                    ]
                );
                
                if (!empty($results['errors'])) {
                    $this->newLine();
                    $this->warn('⚠️  Errors encountered:');
                    foreach ($results['errors'] as $error) {
                        $this->line("• {$error['company']}: {$error['error']}");
                    }
                }
            }
            
            $executionTime = round(microtime(true) - $startTime, 2);
            $this->newLine();
            $this->info("✨ Scraping completed in {$executionTime} seconds");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Scraping failed: ' . $e->getMessage());
            return 1;
        }
    }
}
