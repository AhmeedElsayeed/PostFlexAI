<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'title',
        'caption',
        'type',
        'status',
        'scheduled_at',
        'platforms',
        'video_cover',
        'target_segments',
        'target_personas',
        'engagement_prediction',
        'best_posting_time'
    ];

    protected $casts = [
        'platforms' => 'array',
        'scheduled_at' => 'datetime',
        'target_segments' => 'array',
        'target_personas' => 'array',
        'engagement_prediction' => 'float',
        'best_posting_time' => 'datetime'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class);
    }

    public function targetSegments()
    {
        return $this->belongsToMany(AudienceCluster::class, 'post_target_segments', 'post_id', 'segment_id');
    }

    public function targetPersonas()
    {
        return $this->belongsToMany(AudiencePersona::class, 'post_target_personas', 'post_id', 'persona_id');
    }

    public function getEngagementPredictionLabel(): string
    {
        if ($this->engagement_prediction >= 5) {
            return 'تفاعل متوقع عالي جداً';
        } elseif ($this->engagement_prediction >= 3) {
            return 'تفاعل متوقع جيد';
        } elseif ($this->engagement_prediction >= 1) {
            return 'تفاعل متوقع متوسط';
        } else {
            return 'تفاعل متوقع منخفض';
        }
    }

    public function isScheduled()
    {
        return $this->status === 'scheduled';
    }

    public function isPosted()
    {
        return $this->status === 'posted';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }
} 