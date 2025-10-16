<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FinnhubService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.finnhub.api_key');
        $this->baseUrl = 'https://finnhub.io/api/v1';
    }

    /**
     * Get company earnings data
     */
    public function getEarnings(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/calendar/earnings", [
                'symbol' => $symbol,
                'token' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Finnhub earnings API failed for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Finnhub earnings API error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get company profile data
     */
    public function getCompanyProfile(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/stock/profile2", [
                'symbol' => $symbol,
                'token' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Finnhub company profile API failed for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Finnhub company profile API error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get earnings estimates
     */
    public function getEarningsEstimates(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/stock/earnings", [
                'symbol' => $symbol,
                'token' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Finnhub earnings estimates API failed for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Finnhub earnings estimates API error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get real-time quote data
     */
    public function getQuote(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/quote", [
                'symbol' => $symbol,
                'token' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Finnhub quote API failed for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Finnhub quote API error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get basic financials and metrics
     */
    public function getBasicFinancials(string $symbol): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/stock/metric", [
                'symbol' => $symbol,
                'metric' => 'all',
                'token' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning("Finnhub metrics API failed for {$symbol}", [
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Finnhub metrics API error for {$symbol}", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Get all available data for a company symbol
     * Returns comprehensive data from all Finnhub endpoints
     */
    public function getAllCompanyData(string $symbol): array
    {
        $data = [
            'symbol' => $symbol,
            'timestamp' => now()->toIso8601String(),
            'profile' => null,
            'quote' => null,
            'earnings' => null,
            'earnings_estimates' => null,
            'metrics' => null,
            'errors' => []
        ];

        // Get company profile (includes market cap, name, industry, etc.)
        try {
            $data['profile'] = $this->getCompanyProfile($symbol);
        } catch (\Exception $e) {
            $data['errors'][] = 'Profile: ' . $e->getMessage();
        }

        // Get real-time quote (price, high, low, volume)
        try {
            $data['quote'] = $this->getQuote($symbol);
        } catch (\Exception $e) {
            $data['errors'][] = 'Quote: ' . $e->getMessage();
        }

        // Get earnings calendar
        try {
            $data['earnings'] = $this->getEarnings($symbol);
        } catch (\Exception $e) {
            $data['errors'][] = 'Earnings: ' . $e->getMessage();
        }

        // Get earnings estimates
        try {
            $data['earnings_estimates'] = $this->getEarningsEstimates($symbol);
        } catch (\Exception $e) {
            $data['errors'][] = 'Earnings Estimates: ' . $e->getMessage();
        }

        // Get basic financials and metrics
        try {
            $data['metrics'] = $this->getBasicFinancials($symbol);
        } catch (\Exception $e) {
            $data['errors'][] = 'Metrics: ' . $e->getMessage();
        }

        return $data;
    }

    /**
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
