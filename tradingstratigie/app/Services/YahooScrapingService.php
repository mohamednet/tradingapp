<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YahooScrapingService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://finance.yahoo.com';
    }

    /**
     * Scrape analyst data from Yahoo Finance Analysis page
     */
    public function scrapeAnalystData(string $symbol): ?array
    {
        try {
            $url = "{$this->baseUrl}/quote/{$symbol}/analysis";
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
            ])->timeout(30)->get($url);

            if (!$response->successful()) {
                Log::error("Failed to scrape Yahoo Finance Analysis for {$symbol}", [
                    'status' => $response->status(),
                    'url' => $url
                ]);
                return null;
            }

            $html = $response->body();
            return $this->parseAnalystData($html, $symbol);

        } catch (\Exception $e) {
            Log::error("Exception while scraping Yahoo Finance Analysis for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse analyst data from Analysis page HTML
     */
    private function parseAnalystData(string $html, string $symbol): array
    {
        $data = [
            'symbol' => $symbol,
            'price_target_mean' => null,
            'price_target_high' => null,
            'price_target_low' => null,
            'recommendation' => null,
            'analyst_count' => null,
            'scraped_at' => now()
        ];

        // Extract mean price target (pattern found: "235.52553")
        if (preg_match('/mean.*?(\d+\.\d+)/i', $html, $matches)) {
            $data['price_target_mean'] = (float) $matches[1];
        }

        // Look for price targets in data attributes or JSON - try multiple patterns
        $jsonPatterns = [
            '/"targetMeanPrice":\s*(\d+\.?\d*)/',
            '/"targetHighPrice":\s*(\d+\.?\d*)/', 
            '/"targetLowPrice":\s*(\d+\.?\d*)/',
            '/"recommendationMean":\s*(\d+\.?\d*)/',
            '/"numberOfAnalystOpinions":\s*(\d+)/'
        ];

        // Extract all JSON data in one pass
        if (preg_match('/{[^}]*"targetMeanPrice"[^}]*}/', $html, $jsonMatch)) {
            $jsonData = $jsonMatch[0];
            
            if (preg_match('/"targetMeanPrice":\s*(\d+\.?\d*)/', $jsonData, $matches)) {
                $data['price_target_mean'] = (float) $matches[1];
            }
            if (preg_match('/"targetHighPrice":\s*(\d+\.?\d*)/', $jsonData, $matches)) {
                $data['price_target_high'] = (float) $matches[1];
            }
            if (preg_match('/"targetLowPrice":\s*(\d+\.?\d*)/', $jsonData, $matches)) {
                $data['price_target_low'] = (float) $matches[1];
            }
            if (preg_match('/"recommendationMean":\s*(\d+\.?\d*)/', $jsonData, $matches)) {
                $recommendationValue = (float) $matches[1];
                $data['recommendation'] = $this->convertRecommendationScore($recommendationValue);
            }
            if (preg_match('/"numberOfAnalystOpinions":\s*(\d+)/', $jsonData, $matches)) {
                $data['analyst_count'] = (int) $matches[1];
            }
        }

        // Fallback: search entire HTML for individual patterns
        if (!$data['price_target_mean']) {
            if (preg_match('/"targetMeanPrice":\s*(\d+\.?\d*)/', $html, $matches)) {
                $data['price_target_mean'] = (float) $matches[1];
            }
        }
        
        if (!$data['recommendation']) {
            if (preg_match('/"recommendationMean":\s*(\d+\.?\d*)/', $html, $matches)) {
                $recommendationValue = (float) $matches[1];
                $data['recommendation'] = $this->convertRecommendationScore($recommendationValue);
            }
        }
        
        if (!$data['analyst_count']) {
            if (preg_match('/"numberOfAnalystOpinions":\s*(\d+)/', $html, $matches)) {
                $data['analyst_count'] = (int) $matches[1];
            }
        }

        return $data;
    }

    /**
     * Convert numerical recommendation score to text
     */
    private function convertRecommendationScore(float $score): string
    {
        if ($score <= 1.5) return 'Strong Buy';
        if ($score <= 2.5) return 'Buy';
        if ($score <= 3.5) return 'Hold';
        if ($score <= 4.5) return 'Sell';
        return 'Strong Sell';
    }

    /**
     * Calculate price performance using historical data
     */
    public function calculatePricePerformance(string $symbol, float $currentPrice): array
    {
        $performance = [
            'price_performance_1week' => null,
            'price_performance_1month' => null
        ];

        try {
            // Get historical data from Yahoo Finance API (this works)
            $yahooService = new \App\Services\YahooFinanceService();
            $historicalData = $yahooService->getHistoricalData($symbol, 30); // 30 days

            if ($historicalData && is_array($historicalData) && count($historicalData) > 0) {
                // Historical data comes in reverse chronological order (newest first)
                // Calculate 1-week performance (7 days ago)
                if (count($historicalData) >= 7) {
                    $weekAgoPrice = $historicalData[6]['close']; // 7th day back (index 6)
                    $performance['price_performance_1week'] = round((($currentPrice - $weekAgoPrice) / $weekAgoPrice) * 100, 2);
                }

                // Calculate 1-month performance (use oldest available data)
                $oldestPrice = end($historicalData)['close']; // Last element is oldest
                $performance['price_performance_1month'] = round((($currentPrice - $oldestPrice) / $oldestPrice) * 100, 2);
            }

        } catch (\Exception $e) {
            Log::error("Failed to calculate price performance for {$symbol}", [
                'error' => $e->getMessage()
            ]);
        }

        return $performance;
    }

    /**
     * Scrape stock data from Yahoo Finance website
     */
    public function scrapeStockData(string $symbol): ?array
    {
        try {
            $url = "{$this->baseUrl}/quote/{$symbol}";
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Accept-Encoding' => 'gzip, deflate',
                'Connection' => 'keep-alive',
            ])->timeout(30)->get($url);

            if (!$response->successful()) {
                Log::error("Failed to scrape Yahoo Finance for {$symbol}", [
                    'status' => $response->status(),
                    'url' => $url
                ]);
                return null;
            }

            $html = $response->body();
            return $this->parseStockData($html, $symbol);

        } catch (\Exception $e) {
            Log::error("Exception while scraping Yahoo Finance for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse HTML content to extract stock data
     */
    private function parseStockData(string $html, string $symbol): array
    {
        $data = [
            'symbol' => $symbol,
            'current_price' => null,
            'market_cap' => null,
            'volume' => null,
            'pe_ratio' => null,
            'dividend_yield' => null,
            'fifty_two_week_high' => null,
            'fifty_two_week_low' => null,
            'beta' => null,
            'eps' => null,
            'scraped_at' => now()
        ];

        // Extract data using data-symbol pattern (works with current Yahoo structure)
        if (preg_match_all('/data-symbol="' . $symbol . '"[^>]*>([^<]+)</', $html, $matches)) {
            $values = $matches[1];
            
            // Based on the pattern, values are typically in this order:
            // [0] = current price, [1] = high, [2] = range, [3] = 52w range, [4] = volume, [5] = avg volume, [6] = market cap, etc.
            if (isset($values[0])) {
                $data['current_price'] = $this->cleanNumber($values[0]);
            }
            if (isset($values[6])) {
                $data['market_cap'] = $this->parseMarketCap($values[6]);
            }
            if (isset($values[4])) {
                $data['volume'] = $this->cleanNumber($values[4]);
            }
        }

        // Extract 52-week range from data-symbol matches
        if (preg_match_all('/data-symbol="' . $symbol . '"[^>]*>([^<]+)</', $html, $matches)) {
            foreach ($matches[1] as $value) {
                if (strpos($value, ' - ') !== false && strpos($value, '.') !== false) {
                    $range = explode(' - ', $value);
                    if (count($range) === 2) {
                        $low = $this->cleanNumber($range[0]);
                        $high = $this->cleanNumber($range[1]);
                        
                        // Determine if this is 52-week range (wider range) or daily range
                        if ($low && $high && ($high - $low) > 50) {
                            $data['fifty_two_week_low'] = $low;
                            $data['fifty_two_week_high'] = $high;
                        }
                    }
                }
            }
        }

        // Try alternative selectors for missing data
        if (!$data['current_price']) {
            if (preg_match('/<fin-streamer[^>]*data-field="regularMarketPrice"[^>]*>([^<]+)</', $html, $matches)) {
                $data['current_price'] = $this->cleanNumber($matches[1]);
            }
        }

        return $data;
    }

    /**
     * Clean and convert number strings to float
     */
    private function cleanNumber(string $value): ?float
    {
        // Remove commas and convert to float
        $cleaned = str_replace([',', '$', '%'], '', trim($value));
        
        if ($cleaned === 'N/A' || $cleaned === '--' || empty($cleaned)) {
            return null;
        }

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Parse market cap with suffixes (B, M, T)
     */
    private function parseMarketCap(string $value): ?float
    {
        $cleaned = trim($value);
        
        if ($cleaned === 'N/A' || $cleaned === '--') {
            return null;
        }

        // Extract number and suffix
        if (preg_match('/([0-9,.]+)([BMT]?)/', $cleaned, $matches)) {
            $number = (float) str_replace(',', '', $matches[1]);
            $suffix = $matches[2] ?? '';

            switch ($suffix) {
                case 'T':
                    return $number * 1000000000000; // Trillion
                case 'B':
                    return $number * 1000000000; // Billion
                case 'M':
                    return $number * 1000000; // Million
                default:
                    return $number;
            }
        }

        return null;
    }

    /**
     * Parse dividend yield from dividend string
     */
    private function parseDividendYield(string $value): ?float
    {
        // Format: "1.23 (4.56%)" - extract the percentage
        if (preg_match('/\(([0-9.]+)%\)/', $value, $matches)) {
            return (float) $matches[1];
        }

        return null;
    }

    /**
     * Scrape earnings date from Yahoo Finance
     */
    public function scrapeEarningsDate(string $symbol): ?array
    {
        try {
            $url = "{$this->baseUrl}/quote/{$symbol}";
            
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Connection' => 'keep-alive',
            ])->timeout(30)->get($url);

            if (!$response->successful()) {
                Log::warning("Failed to scrape Yahoo Finance earnings for {$symbol}", [
                    'status' => $response->status()
                ]);
                return null;
            }

            $html = $response->body();
            return $this->parseEarningsDate($html, $symbol);

        } catch (\Exception $e) {
            Log::error("Exception while scraping earnings date for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse earnings date from Yahoo Finance HTML
     */
    private function parseEarningsDate(string $html, string $symbol): ?array
    {
        try {
            // Method 1: Look for "Earnings Date" in the page
            // Pattern: <span>Earnings Date</span>...<span>Oct 22, 2025 - Oct 28, 2025</span>
            if (preg_match('/Earnings Date.*?<span[^>]*>([^<]+)<\/span>/s', $html, $matches)) {
                $dateText = trim($matches[1]);
                
                // Handle date ranges like "Oct 22, 2025 - Oct 28, 2025"
                if (strpos($dateText, ' - ') !== false) {
                    $dates = explode(' - ', $dateText);
                    $earningsDate = trim($dates[0]); // Use the first date
                } else {
                    $earningsDate = $dateText;
                }
                
                // Try to parse the date
                try {
                    $parsedDate = \Carbon\Carbon::parse($earningsDate);
                    
                    // Only return future dates
                    if ($parsedDate->isFuture()) {
                        return [
                            'earnings_date' => $parsedDate->format('Y-m-d'),
                            'date_text' => $dateText,
                            'source' => 'yahoo_finance',
                            'scraped_at' => now()->toISOString()
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to parse earnings date for {$symbol}: {$earningsDate}");
                }
            }

            // Method 2: Try to find it in JSON data embedded in the page
            if (preg_match('/"earningsTimestamp":\{"raw":(\d+)/', $html, $matches)) {
                $timestamp = (int) $matches[1];
                $earningsDate = \Carbon\Carbon::createFromTimestamp($timestamp);
                
                if ($earningsDate->isFuture()) {
                    return [
                        'earnings_date' => $earningsDate->format('Y-m-d'),
                        'date_text' => $earningsDate->format('M d, Y'),
                        'source' => 'yahoo_finance_json',
                        'scraped_at' => now()->toISOString()
                    ];
                }
            }

            // Method 3: Look for "earningsDate" in JSON
            if (preg_match('/"earningsDate":\{"fmt":"([^"]+)"/', $html, $matches)) {
                $dateText = $matches[1];
                try {
                    $parsedDate = \Carbon\Carbon::parse($dateText);
                    
                    if ($parsedDate->isFuture()) {
                        return [
                            'earnings_date' => $parsedDate->format('Y-m-d'),
                            'date_text' => $dateText,
                            'source' => 'yahoo_finance_json',
                            'scraped_at' => now()->toISOString()
                        ];
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to parse JSON earnings date for {$symbol}: {$dateText}");
                }
            }

            Log::info("No future earnings date found for {$symbol}");
            return null;

        } catch (\Exception $e) {
            Log::error("Error parsing earnings date for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
