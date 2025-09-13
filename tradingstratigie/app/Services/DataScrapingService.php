<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Earning;
use App\Models\CompanyFinancialData;
use App\Models\ScrapingLog;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DataScrapingService
{
    private FinnhubService $finnhubService;
    private YahooFinanceService $yahooService;

    public function __construct(FinnhubService $finnhubService, YahooFinanceService $yahooService)
    {
        $this->finnhubService = $finnhubService;
        $this->yahooService = $yahooService;
    }

    /**
     * Process all companies and update their data using chunk processing
     */
    public function processAllCompanies(): array
    {
        $startTime = microtime(true);
        $results = [
            'total_companies' => 0,
            'successful_companies' => 0,
            'failed_companies' => 0,
            'errors' => []
        ];

        $results['total_companies'] = Company::count();
        Log::info("Starting data scraping for {$results['total_companies']} companies");

        // Process companies in chunks of 5 for better performance
        Company::chunk(5, function ($companies) use (&$results, $startTime) {
            foreach ($companies as $company) {
                try {
                    $this->processCompany($company);
                    $results['successful_companies']++;
                    
                    // Reduced delay for chunk processing
                    usleep(500000); // 0.5 seconds instead of 1 second
                    
                } catch (\Exception $e) {
                    $results['failed_companies']++;
                    $results['errors'][] = [
                        'company' => $company->symbol,
                        'error' => $e->getMessage()
                    ];

                    $this->logScrapingError($company, 'full_scrape', $e->getMessage(), microtime(true) - $startTime);
                    
                    Log::error("Failed to process company {$company->symbol}", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        });

        $totalTime = microtime(true) - $startTime;
        Log::info("Data scraping completed", [
            'total_time' => $totalTime,
            'results' => $results
        ]);

        return $results;
    }

    /**
     * Process a single company
     */
    public function processCompany(Company $company): void
    {
        $startTime = microtime(true);
        
        Log::info("Processing company: {$company->symbol}");

        // Fetch earnings data from Finnhub
        $this->updateEarningsData($company);
        
        // Fetch financial data from Yahoo Finance
        $this->updateFinancialData($company);
        
        $executionTime = microtime(true) - $startTime;
        
        // Log successful scraping
        $this->logScrapingSuccess($company, 'full_scrape', $executionTime);
    }

    /**
     * Update earnings data using Finnhub
     */
    private function updateEarningsData(Company $company): void
    {
        try {
            // Get earnings calendar
            $earningsData = $this->finnhubService->getEarnings($company->symbol);
            
            if (!$earningsData) {
                Log::warning("No earnings data found for {$company->symbol}");
                return;
            }

            // Get earnings estimates
            $estimatesData = $this->finnhubService->getEarningsEstimates($company->symbol);

            // Process and save earnings data
            if (isset($earningsData['earningsCalendar']) && !empty($earningsData['earningsCalendar'])) {
                foreach ($earningsData['earningsCalendar'] as $earning) {
                    $this->saveEarningRecord($company, $earning, $estimatesData);
                }
            }

        } catch (\Exception $e) {
            Log::error("Failed to update earnings data for {$company->symbol}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update financial data for a company using web scraping
     */
    private function updateFinancialData(Company $company): void
    {
        $startTime = microtime(true);
        
        try {
            // Use YahooScrapingService for reliable data
            $scrapingService = new \App\Services\YahooScrapingService();
            
            // Get stock data from scraping
            $stockData = $scrapingService->scrapeStockData($company->symbol);
            
            // Get analyst data from Analysis page
            $analystData = $scrapingService->scrapeAnalystData($company->symbol);
            
            // Calculate price performance if we have current price
            $performance = [];
            if ($stockData && $stockData['current_price']) {
                $performance = $scrapingService->calculatePricePerformance($company->symbol, $stockData['current_price']);
            }
            
            $this->saveScrapedFinancialData($company, $stockData, $analystData, $performance);
            
            $executionTime = microtime(true) - $startTime;
            $this->logScrapingSuccess($company, 'financial_data', $executionTime);
            
        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            $this->logScrapingError($company, 'financial_data', $e->getMessage(), $executionTime);
            throw $e;
        }
    }

    /**
     * Save scraped financial data to database
     */
    private function saveScrapedFinancialData(Company $company, ?array $stockData, ?array $analystData, array $performance): void
    {
        // Check if record exists for today
        $existingData = CompanyFinancialData::where('company_id', $company->id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        $data = [
            'company_id' => $company->id,
            'current_stock_price' => $stockData['current_price'] ?? null,
            'market_cap' => $stockData['market_cap'] ?? null,
            'average_daily_volume' => $stockData['volume'] ?? null,
            'price_performance_1week' => $performance['price_performance_1week'] ?? null,
            'price_performance_1month' => $performance['price_performance_1month'] ?? null,
            'fair_value_estimate' => $analystData['price_target_mean'] ?? null,
            'data_sources' => [
                'scraped_stock_data' => $stockData,
                'scraped_analyst_data' => $analystData,
                'performance_data' => $performance
            ],
            'last_scraped_at' => Carbon::now()
        ];

        if ($existingData) {
            $existingData->update($data);
        } else {
            CompanyFinancialData::create($data);
        }
    }

    /**
     * Save earnings record to database
     */
    private function saveEarningRecord(Company $company, array $earningData, ?array $estimatesData): void
    {
        $earningsDate = isset($earningData['date']) ? Carbon::parse($earningData['date']) : null;
        
        if (!$earningsDate) {
            return;
        }

        // Check if record already exists
        $existingEarning = Earning::where('company_id', $company->id)
            ->where('earnings_release_date', $earningsDate)
            ->first();

        $data = [
            'company_id' => $company->id,
            'earnings_release_date' => $earningsDate,
            'estimated_revenue' => $earningData['revenueEstimate'] ?? null,
            'actual_revenue' => $earningData['revenueActual'] ?? null,
            'api_data' => [
                'finnhub_earnings' => $earningData,
                'finnhub_estimates' => $estimatesData
            ]
        ];

        if ($existingEarning) {
            $existingEarning->update($data);
        } else {
            Earning::create($data);
        }
    }

    /**
     * Save financial data to database
     */
    private function saveFinancialData(Company $company, array $quoteData, array $statsData, ?array $historicalData, ?array $analystData): void
    {
        // Extract current price from quote data
        $currentPrice = $quoteData['chart']['result'][0]['meta']['regularMarketPrice'] ?? null;
        
        // Extract market cap and volume from stats
        $marketCap = $statsData['quoteSummary']['result'][0]['summaryDetail']['marketCap']['raw'] ?? null;
        $avgVolume = $statsData['quoteSummary']['result'][0]['summaryDetail']['averageVolume']['raw'] ?? null;
        
        // Calculate price performance
        $performance1Week = null;
        $performance1Month = null;
        
        if ($historicalData) {
            $performance1Week = $this->yahooService->calculatePricePerformance($historicalData, 7);
            $performance1Month = $this->yahooService->calculatePricePerformance($historicalData, 30);
        }
        
        // Extract fair value estimate from analyst data
        $fairValue = null;
        if ($analystData && isset($analystData['quoteSummary']['result'][0]['financialData']['targetMeanPrice']['raw'])) {
            $fairValue = $analystData['quoteSummary']['result'][0]['financialData']['targetMeanPrice']['raw'];
        }

        // Check if record exists for today
        $existingData = CompanyFinancialData::where('company_id', $company->id)
            ->whereDate('created_at', Carbon::today())
            ->first();

        $data = [
            'company_id' => $company->id,
            'current_stock_price' => $currentPrice,
            'market_cap' => $marketCap,
            'average_daily_volume' => $avgVolume,
            'price_performance_1week' => $performance1Week,
            'price_performance_1month' => $performance1Month,
            'fair_value_estimate' => $fairValue,
            'data_sources' => [
                'yahoo_quote' => $quoteData,
                'yahoo_stats' => $statsData,
                'yahoo_historical' => $historicalData,
                'yahoo_analyst' => $analystData
            ],
            'last_scraped_at' => Carbon::now()
        ];

        if ($existingData) {
            $existingData->update($data);
        } else {
            CompanyFinancialData::create($data);
        }
    }

    /**
     * Log successful scraping
     */
    private function logScrapingSuccess(Company $company, string $scrapeType, float $executionTime): void
    {
        ScrapingLog::create([
            'company_id' => $company->id,
            'scrape_type' => $scrapeType,
            'status' => 'success',
            'execution_time' => round($executionTime * 1000), // Convert to milliseconds
            'scraped_count' => 1,
            'created_at' => Carbon::now()
        ]);
    }

    /**
     * Log scraping error
     */
    private function logScrapingError(Company $company, string $scrapeType, string $errorMessage, float $executionTime): void
    {
        ScrapingLog::create([
            'company_id' => $company->id,
            'scrape_type' => $scrapeType,
            'status' => 'error',
            'error_message' => $errorMessage,
            'execution_time' => round($executionTime * 1000), // Convert to milliseconds
            'scraped_count' => 0,
            'created_at' => Carbon::now()
        ]);
    }
}
