<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'symbol' => $this->symbol,
            'name' => $this->name,
            'favorite' => $this->favorite,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Financial Data
            'financial_data' => $this->when($this->relationLoaded('financialData') && $this->financialData, function() {
                $financial = $this->financialData;
                return [
                    'current_stock_price' => $financial->current_stock_price,
                    'market_cap' => $financial->market_cap,
                    'market_cap_formatted' => $financial->market_cap 
                        ? '$' . $this->formatNumber($financial->market_cap)
                        : null,
                    'average_daily_volume' => $financial->average_daily_volume,
                    'volume_formatted' => $financial->average_daily_volume 
                        ? $this->formatNumber($financial->average_daily_volume)
                        : null,
                    'week_52_high' => $financial->week_52_high,
                    'week_52_low' => $financial->week_52_low,
                    'price_performance_1week' => $financial->price_performance_1week,
                    'price_performance_1month' => $financial->price_performance_1month,
                    'fair_value_estimate' => $financial->fair_value_estimate,
                    'last_scraped_at' => $financial->last_scraped_at?->toISOString(),
                    'data_freshness' => $financial->last_scraped_at 
                        ? $financial->last_scraped_at->diffForHumans()
                        : null
                ];
            }),
            
            // Earnings Data
            'earnings' => $this->when($this->relationLoaded('earnings'), 
                EarningResource::collection($this->earnings)
            ),
            
            // Summary Stats
            'summary' => [
                'has_financial_data' => $this->relationLoaded('financialData') && $this->financialData !== null,
                'has_earnings_data' => $this->relationLoaded('earnings') && $this->earnings->count() > 0,
                'earnings_count' => $this->when($this->relationLoaded('earnings'), $this->earnings->count()),
                'latest_earnings_date' => $this->when(
                    $this->relationLoaded('earnings') && $this->earnings->count() > 0,
                    $this->earnings->sortByDesc('earnings_release_date')->first()?->earnings_release_date?->toDateString()
                )
            ]
        ];
    }

    /**
     * Format large numbers with appropriate suffixes
     */
    private function formatNumber($number): string
    {
        if ($number >= 1000000000000) {
            return number_format($number / 1000000000000, 2) . 'T';
        } elseif ($number >= 1000000000) {
            return number_format($number / 1000000000, 2) . 'B';
        } elseif ($number >= 1000000) {
            return number_format($number / 1000000, 2) . 'M';
        } elseif ($number >= 1000) {
            return number_format($number / 1000, 2) . 'K';
        }
        
        return number_format($number, 2);
    }
}
