<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'endpoint',
        'endpoint_hash',
        'p256dh_key',
        'auth_token',
        'user_agent',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate endpoint hash
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($subscription) {
            if (!$subscription->endpoint_hash) {
                $subscription->endpoint_hash = hash('sha256', $subscription->endpoint);
            }
        });
        
        static::updating(function ($subscription) {
            if ($subscription->isDirty('endpoint')) {
                $subscription->endpoint_hash = hash('sha256', $subscription->endpoint);
            }
        });
    }
}
