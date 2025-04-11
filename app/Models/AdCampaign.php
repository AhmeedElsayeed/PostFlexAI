<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdCampaign extends Model
{
    protected $fillable = [
        'name',
        'platform',
        'status',
        'budget',
        'start_date',
        'end_date',
        'target_audience',
        'user_id',
        'ad_account_id',
        'campaign_type',
        'objective',
        'daily_budget',
        'total_budget',
        'bid_strategy',
        'targeting',
        'placement',
        'creative_type'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'target_audience' => 'array',
        'targeting' => 'array',
        'placement' => 'array',
        'budget' => 'decimal:2',
        'daily_budget' => 'decimal:2',
        'total_budget' => 'decimal:2'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function adSets(): HasMany
    {
        return $this->hasMany(AdSet::class);
    }

    public function ads(): HasMany
    {
        return $this->hasMany(Ad::class);
    }
} 