<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceCluster extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'characteristics',
        'content_recommendations',
        'best_posting_times'
    ];

    protected $casts = [
        'characteristics' => 'array',
        'content_recommendations' => 'array',
        'best_posting_times' => 'array'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getSizeAttribute()
    {
        return $this->characteristics['size'] ?? 0;
    }

    public function getEngagementRateAttribute()
    {
        return $this->characteristics['engagement_rate'] ?? 0;
    }

    public function getTopContentTypeAttribute()
    {
        if (!$this->content_recommendations) {
            return null;
        }

        arsort($this->content_recommendations);
        return array_key_first($this->content_recommendations);
    }

    public function getBestPostingTimeAttribute()
    {
        if (!$this->best_posting_times) {
            return null;
        }

        arsort($this->best_posting_times);
        return array_key_first($this->best_posting_times);
    }
} 