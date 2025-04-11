<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceComparison extends Model
{
    protected $fillable = [
        'team_id',
        'social_account_id',
        'metric_type',
        'current_period_data',
        'previous_period_data',
        'change_percentage',
        'period_type',
        'current_period_start',
        'current_period_end',
        'previous_period_start',
        'previous_period_end',
        'insights'
    ];

    protected $casts = [
        'current_period_data' => 'array',
        'previous_period_data' => 'array',
        'insights' => 'array',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'previous_period_start' => 'date',
        'previous_period_end' => 'date',
        'change_percentage' => 'float'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function getMetricLabel(): string
    {
        return match($this->metric_type) {
            'engagement' => 'معدل التفاعل',
            'growth' => 'معدل النمو',
            'demographics' => 'الخصائص الديموغرافية',
            'behavior' => 'السلوك',
            default => $this->metric_type
        };
    }

    public function getPeriodLabel(): string
    {
        return match($this->period_type) {
            'week' => 'أسبوع',
            'month' => 'شهر',
            'quarter' => 'ربع سنة',
            'year' => 'سنة',
            default => $this->period_type
        };
    }

    public function getChangeLabel(): string
    {
        $direction = $this->change_percentage >= 0 ? 'زيادة' : 'انخفاض';
        return "{$direction} بنسبة " . abs($this->change_percentage) . '%';
    }
} 