<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdSet extends Model
{
    protected $fillable = [
        'name',
        'campaign_id',
        'ad_account_id',
        'status',
        'budget',
        'bid_amount',
        'targeting',
        'optimization_goal',
        'billing_event',
        'start_time',
        'end_time',
        'target_audience',
        'placement',
        'bid_strategy',
        'creative_type'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'target_audience' => 'array',
        'targeting' => 'array',
        'placement' => 'array',
        'budget' => 'decimal:2',
        'bid_amount' => 'decimal:2'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
} 