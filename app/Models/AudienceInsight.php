<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceInsight extends Model
{
    protected $fillable = [
        'social_account_id',
        'platform',
        'demographics',
        'interests',
        'top_active_hours',
        'content_preferences',
        'engagement_metrics',
        'growth_metrics'
    ];

    protected $casts = [
        'demographics' => 'array',
        'interests' => 'array',
        'top_active_hours' => 'array',
        'content_preferences' => 'array',
        'engagement_metrics' => 'array',
        'growth_metrics' => 'array'
    ];

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function getEngagementRateAttribute()
    {
        if (!$this->engagement_metrics || !$this->growth_metrics) {
            return 0;
        }

        $totalEngagements = array_sum($this->engagement_metrics);
        $totalReach = array_sum($this->growth_metrics['reach'] ?? []);

        return $totalReach > 0 ? ($totalEngagements / $totalReach) * 100 : 0;
    }

    public function getTopInterestAttribute()
    {
        if (!$this->interests) {
            return null;
        }

        arsort($this->interests);
        return array_key_first($this->interests);
    }

    public function getBestPostingTimeAttribute()
    {
        if (!$this->top_active_hours) {
            return null;
        }

        arsort($this->top_active_hours);
        return array_key_first($this->top_active_hours);
    }
} 