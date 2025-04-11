<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountInsight extends Model
{
    protected $fillable = [
        'social_account_id',
        'followers',
        'posts_count',
        'reach',
        'impressions',
        'engagement_rate',
        'fetched_at'
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
        'engagement_rate' => 'decimal:2'
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function calculateEngagementRate(): float
    {
        if ($this->reach === 0) {
            return 0;
        }

        return round(($this->impressions / $this->reach) * 100, 2);
    }
} 