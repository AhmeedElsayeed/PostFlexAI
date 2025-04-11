<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIModelFeedback extends Model
{
    protected $table = 'ai_model_feedback';

    protected $fillable = [
        'team_id',
        'model_type',
        'feedback_type',
        'feedback_text',
        'context_data',
        'suggested_improvements',
        'is_resolved',
        'resolution_notes'
    ];

    protected $casts = [
        'context_data' => 'array',
        'suggested_improvements' => 'array',
        'is_resolved' => 'boolean'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getModelTypeLabel(): string
    {
        return match($this->model_type) {
            'audience_analysis' => 'تحليل الجمهور',
            'content_suggestions' => 'اقتراحات المحتوى',
            'engagement_prediction' => 'توقع التفاعل',
            'best_time_prediction' => 'توقع أفضل وقت للنشر',
            default => $this->model_type
        };
    }

    public function getFeedbackTypeLabel(): string
    {
        return match($this->feedback_type) {
            'positive' => 'إيجابي',
            'negative' => 'سلبي',
            'neutral' => 'محايد',
            default => $this->feedback_type
        };
    }

    public function resolve(string $notes): void
    {
        $this->update([
            'is_resolved' => true,
            'resolution_notes' => $notes
        ]);
    }
} 