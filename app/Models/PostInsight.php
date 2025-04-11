<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostInsight extends Model
{
    protected $fillable = [
        'post_id',
        'platform',
        'likes',
        'comments',
        'shares',
        'views',
        'saves',
        'engagement_rate',
        'fetched_at'
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
        'engagement_rate' => 'decimal:2'
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function calculateEngagementRate(): float
    {
        $totalEngagement = $this->likes + $this->comments + $this->shares;
        $reach = $this->views ?? 0;
        
        if ($reach === 0) {
            return 0;
        }

        return round(($totalEngagement / $reach) * 100, 2);
    }
} 