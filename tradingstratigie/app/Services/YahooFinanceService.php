<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YahooFinanceService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'https://query1.finance.yahoo.com';
    }

    /**
     * Get real-time quote data
     */
    public function getQuote(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/v8/finance/chart/{$symbol}");

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Yahoo Finance quote API failed for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Yahoo Finance quote API error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get company statistics (market cap, volume, etc.)
     */
    public function getStatistics(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/v10/finance/quoteSummary/{$symbol}", [
                'modules' => 'defaultKeyStatistics,summaryDetail,price'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Yahoo Finance statistics API failed for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Yahoo Finance statistics API error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get historical price data for performance calculations
     */
    public function getHistoricalData(string $symbol, int $days = 30): ?array
    {
        try {
            $endTime = time();
            $startTime = $endTime - ($days * 24 * 60 * 60);

            $response = Http::get("{$this->baseUrl}/v8/finance/chart/{$symbol}", [
                'period1' => $startTime,
                'period2' => $endTime,
                'interval' => '1d'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $this->parseHistoricalData($data);
            }

            Log::warning("Yahoo Finance historical API failed for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Yahoo Finance historical API error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get analyst recommendations and price targets
     */
    public function getAnalystData(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/v10/finance/quoteSummary/{$symbol}", [
                'modules' => 'recommendationTrend,financialData,upgradeDowngradeHistory'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Yahoo Finance analyst API failed for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Yahoo Finance analyst API error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Parse historical data from Yahoo Finance API response
     */
    private function parseHistoricalData(array $data): array
    {
        $result = [];
        
        try {
            $chart = $data['chart']['result'][0] ?? null;
            if (!$chart) {
                return $result;
            }
            
            $timestamps = $chart['timestamp'] ?? [];
            $quotes = $chart['indicators']['quote'][0] ?? [];
            $closes = $quotes['close'] ?? [];
            $opens = $quotes['open'] ?? [];
            $highs = $quotes['high'] ?? [];
            $lows = $quotes['low'] ?? [];
            $volumes = $quotes['volume'] ?? [];
            
            for ($i = 0; $i < count($timestamps); $i++) {
                $result[] = [
                    'date' => date('Y-m-d', $timestamps[$i]),
                    'open' => $opens[$i] ?? null,
                    'high' => $highs[$i] ?? null,
                    'low' => $lows[$i] ?? null,
                    'close' => $closes[$i] ?? null,
                    'volume' => $volumes[$i] ?? null,
                ];
            }
            
            // Reverse to get newest first (for easier indexing)
            return array_reverse($result);
            
        } catch (\Exception $e) {
            Log::error("Failed to parse historical data", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Calculate price performance percentage
     */
    public function calculatePricePerformance(array $historicalData, int $days): ?float
    {
        try {
            $prices = $historicalData['chart']['result'][0]['indicators']['quote'][0]['close'] ?? [];
            
            if (count($prices) < 2) {
                return null;
            }

            $currentPrice = end($prices);
            $pastPrice = $prices[max(0, count($prices) - $days - 1)];

            if ($pastPrice && $currentPrice) {
                return (($currentPrice - $pastPrice) / $pastPrice) * 100;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Price performance calculation error", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
