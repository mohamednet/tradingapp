<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapingLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'scrape_type',
        'status',
        'error_message',
        'execution_time',
        'scraped_count',
        'created_at'
    ];

    protected $casts = [
        'execution_time' => 'integer',
        'scraped_count' => 'integer',
        'created_at' => 'datetime'
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
