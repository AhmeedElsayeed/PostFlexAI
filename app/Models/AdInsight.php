<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdInsight extends Model
{
    protected $fillable = [
        'ad_id',
        'ad_account_id',
        'date',
        'impressions',
        'clicks',
        'spend',
        'cpc',
        'ctr',
        'conversions',
        'conversion_rate',
        'cost_per_conversion',
        'breakdown_data'
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'clicks' => 'integer',
        'spend' => 'decimal:2',
        'cpc' => 'decimal:2',
        'ctr' => 'decimal:2',
        'conversions' => 'integer',
        'conversion_rate' => 'decimal:2',
        'cost_per_conversion' => 'decimal:2',
        'breakdown_data' => 'array'
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }
} 