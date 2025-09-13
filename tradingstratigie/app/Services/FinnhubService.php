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
     * Check if API key is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
