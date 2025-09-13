<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Earning extends Model
{
    protected $fillable = [
        'company_id',
        'earnings_release_date',
        'estimated_revenue',
        'actual_revenue',
        'api_data'
    ];

    protected $casts = [
        'earnings_release_date' => 'date',
        'estimated_revenue' => 'decimal:2',
        'actual_revenue' => 'decimal:2',
        'api_data' => 'array'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
