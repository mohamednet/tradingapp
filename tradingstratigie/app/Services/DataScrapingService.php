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
     * Update earnings data using Finnhub API + Yahoo Finance fallback
     */
    private function updateEarningsData(Company $company): void
    {
        try {
            // Try to get earnings from Finnhub first
            $finnhubEarnings = $this->finnhubService->getEarnings($company->symbol);
            
            $earningsDate = null;
            $estimatedRevenue = null;
            
            // Check if Finnhub has upcoming earnings
            if ($finnhubEarnings && isset($finnhubEarnings['earningsCalendar']) && count($finnhubEarnings['earningsCalendar']) > 0) {
                $nextEarning = $finnhubEarnings['earningsCalendar'][0];
                $earningsDate = $nextEarning['date'] ?? null;
                
                // Finnhub doesn't provide revenue estimates directly, get from estimates API
                $estimatesData = $this->finnhubService->getEarningsEstimates($company->symbol);
                
                if ($earningsDate) {
                    $this->saveEarningRecordFromFinnhub($company, $nextEarning, $estimatesData);
                    Log::info("Updated earnings record for {$company->symbol}: {$earningsDate}");
                }
            }
            
            // Fallback to Yahoo Finance scraping if Finnhub doesn't have data
            if (!$earningsDate) {
                $scrapingService = new \App\Services\YahooScrapingService();
                $earningsData = $scrapingService->scrapeEarningsDate($company->symbol);
                
                if ($earningsData) {
                    $estimatesData = $this->finnhubService->getEarningsEstimates($company->symbol);
                    $this->saveEarningRecordFromYahoo($company, $earningsData, $estimatesData);
                    Log::info("Updated earnings record for {$company->symbol} from Yahoo: {$earningsData['earnings_date']}");
                } else {
                    Log::info("No upcoming earnings date found for {$company->symbol}");
                }
            }
            
            // Clean up old earnings records
            $this->cleanupOldEarnings($company);

        } catch (\Exception $e) {
            Log::error("Failed to update earnings data for {$company->symbol}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Clean up old earnings records that are in the past or outdated
     */
    private function cleanupOldEarnings(Company $company): void
    {
        // Delete earnings that are in the past
        $deletedPast = Earning::where('company_id', $company->id)
            ->where('earnings_release_date', '<', now())
            ->delete();
            
        // Keep only the most recently created record if there are multiple future dates
        $futureEarnings = Earning::where('company_id', $company->id)
            ->where('earnings_release_date', '>=', now())
            ->orderBy('created_at', 'desc')
            ->get();
            
        if ($futureEarnings->count() > 1) {
            // Keep only the most recently created record (most up-to-date)
            $toKeep = $futureEarnings->first()->id;
            $deletedDuplicates = Earning::where('company_id', $company->id)
                ->where('earnings_release_date', '>=', now())
                ->where('id', '!=', $toKeep)
                ->delete();
                
            if ($deletedDuplicates > 0) {
                Log::info("Removed {$deletedDuplicates} duplicate/outdated earnings for {$company->symbol}");
            }
        }
            
        if ($deletedPast > 0) {
            Log::info("Cleaned up {$deletedPast} past earnings records for {$company->symbol}");
        }
    }

    /**
     * Update financial data for a company using Finnhub API + Yahoo scraping
     */
    private function updateFinancialData(Company $company): void
    {
        $startTime = microtime(true);
        
        try {
            // Get all data from Finnhub
            $finnhubData = $this->finnhubService->getAllCompanyData($company->symbol);
            
            // Extract data from Finnhub
            $currentPrice = $finnhubData['quote']['c'] ?? null;
            $previousClose = $finnhubData['quote']['pc'] ?? null;
            $marketCap = isset($finnhubData['profile']['marketCapitalization']) 
                ? $finnhubData['profile']['marketCapitalization'] * 1000000 
                : null;
            $volume = $finnhubData['metrics']['metric']['10DayAverageTradingVolume'] ?? null;
            
            // Calculate 1-week performance from current vs previous close
            $pricePerformance1Week = null;
            if ($currentPrice && $previousClose && $previousClose > 0) {
                $pricePerformance1Week = (($currentPrice - $previousClose) / $previousClose) * 100;
            }
            
            // Get additional data from Yahoo scraping for fields Finnhub doesn't have
            $scrapingService = new \App\Services\YahooScrapingService();
            
            // Get analyst data (fair value estimate)
            $analystData = $scrapingService->scrapeAnalystData($company->symbol);
            $fairValueEstimate = $analystData['price_target_mean'] ?? null;
            
            // Calculate 1-month performance from Yahoo historical data
            $pricePerformance1Month = null;
            try {
                $performance = $scrapingService->calculatePricePerformance($company->symbol, $currentPrice);
                $pricePerformance1Month = $performance['price_performance_1month'] ?? null;
            } catch (\Exception $e) {
                Log::debug("Could not calculate 1-month performance for {$company->symbol}");
            }
            
            // Prepare data for saving
            $stockData = [
                'current_price' => $currentPrice,
                'market_cap' => $marketCap,
                'volume' => $volume
            ];
            
            $performanceData = [
                'price_performance_1week' => $pricePerformance1Week,
                'price_performance_1month' => $pricePerformance1Month
            ];
            
            $analystDataArray = [
                'price_target_mean' => $fairValueEstimate
            ];
            
            // Save with Finnhub data included
            $this->saveScrapedFinancialData($company, $stockData, $analystDataArray, $performanceData, $finnhubData);
            
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
    private function saveScrapedFinancialData(Company $company, ?array $stockData, ?array $analystData, array $performance, ?array $finnhubData = null): void
    {
        // Get existing record for this company (regardless of date)
        $existingData = CompanyFinancialData::where('company_id', $company->id)->first();

        $data = [
            'company_id' => $company->id,
            'current_stock_price' => $stockData['current_price'] ?? null,
            'market_cap' => $stockData['market_cap'] ?? null,
            'average_daily_volume' => $stockData['volume'] ?? null,
            'price_performance_1week' => $performance['price_performance_1week'] ?? null,
            'price_performance_1month' => $performance['price_performance_1month'] ?? null,
            'fair_value_estimate' => $analystData['price_target_mean'] ?? null,
            'data_sources' => [
                'finnhub_data' => $finnhubData,
                'scraped_stock_data' => $stockData,
                'scraped_analyst_data' => $analystData,
                'performance_data' => $performance
            ],
            'last_scraped_at' => Carbon::now()
        ];

        if ($existingData) {
            // Update existing record with fresh data
            $existingData->update($data);
            \Log::info("Updated financial data for {$company->symbol}", [
                'price' => $data['current_stock_price'],
                'market_cap' => $data['market_cap'] ? '$' . number_format($data['market_cap'] / 1000000000, 2) . 'B' : 'NULL'
            ]);
        } else {
            // Create new record
            CompanyFinancialData::create($data);
            \Log::info("Created financial data for {$company->symbol}", [
                'price' => $data['current_stock_price'],
                'market_cap' => $data['market_cap'] ? '$' . number_format($data['market_cap'] / 1000000000, 2) . 'B' : 'NULL'
            ]);
        }
    }

    /**
     * Save earnings record from Yahoo Finance
     */
    private function saveEarningRecordFromYahoo(Company $company, array $earningsData, ?array $estimatesData): void
    {
        $earningsDate = Carbon::parse($earningsData['earnings_date']);
        
        // Check if record already exists
        $existingEarning = Earning::where('company_id', $company->id)
            ->where('earnings_release_date', $earningsDate)
            ->first();

        $data = [
            'company_id' => $company->id,
            'earnings_release_date' => $earningsDate,
            'estimated_revenue' => null, // Yahoo doesn't provide this directly
            'actual_revenue' => null,
            'api_data' => [
                'yahoo_earnings' => $earningsData,
                'finnhub_estimates' => $estimatesData
            ]
        ];

        if ($existingEarning) {
            $existingEarning->update($data);
            Log::info("Updated earnings record for {$company->symbol}: {$earningsDate->format('Y-m-d')}");
        } else {
            Earning::create($data);
            Log::info("Created earnings record for {$company->symbol}: {$earningsDate->format('Y-m-d')}");
        }
    }

    /**
     * Save earnings record from Finnhub
     */
    private function saveEarningRecordFromFinnhub(Company $company, array $earningData, ?array $estimatesData): void
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
