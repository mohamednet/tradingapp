<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFinancialData extends Model
{
    protected $fillable = [
        'company_id',
        'current_stock_price',
        'sentiment_score',
        'financial_signal',
        'market_cap',
        'average_daily_volume',
        'price_performance_1week',
        'price_performance_1month',
        'fair_value_estimate',
        'data_sources',
        'last_scraped_at'
    ];

    protected $casts = [
        'current_stock_price' => 'decimal:2',
        'sentiment_score' => 'decimal:2',
        'market_cap' => 'integer',
        'average_daily_volume' => 'integer',
        'price_performance_1week' => 'decimal:4',
        'price_performance_1month' => 'decimal:4',
        'fair_value_estimate' => 'decimal:2',
        'data_sources' => 'array',
        'last_scraped_at' => 'datetime'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
