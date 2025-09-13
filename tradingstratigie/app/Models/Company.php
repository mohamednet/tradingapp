<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $fillable = [
        'name',
        'symbol',
        'favorite'
    ];

    protected $casts = [
        'favorite' => 'integer'
    ];

    public function earnings(): HasMany
    {
        return $this->hasMany(Earning::class);
    }

    public function financialData(): HasOne
    {
        return $this->hasOne(CompanyFinancialData::class);
    }

    public function scrapingLogs(): HasMany
    {
        return $this->hasMany(ScrapingLog::class);
    }
}
