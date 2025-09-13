<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EarningResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'earnings_release_date' => $this->earnings_release_date?->toDateString(),
            'earnings_release_date_formatted' => $this->earnings_release_date?->format('M j, Y'),
            'estimated_revenue' => $this->estimated_revenue,
            'actual_revenue' => $this->actual_revenue,
            'revenue_beat' => $this->actual_revenue && $this->estimated_revenue 
                ? $this->actual_revenue > $this->estimated_revenue
                : null,
            'revenue_difference' => $this->actual_revenue && $this->estimated_revenue 
                ? $this->actual_revenue - $this->estimated_revenue
                : null,
            'revenue_difference_percent' => $this->actual_revenue && $this->estimated_revenue && $this->estimated_revenue != 0
                ? round((($this->actual_revenue - $this->estimated_revenue) / $this->estimated_revenue) * 100, 2)
                : null,
            'days_until_earnings' => $this->earnings_release_date 
                ? $this->earnings_release_date->diffInDays(now(), false)
                : null,
            'is_upcoming' => $this->earnings_release_date 
                ? $this->earnings_release_date->isFuture()
                : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString()
        ];
    }
}
