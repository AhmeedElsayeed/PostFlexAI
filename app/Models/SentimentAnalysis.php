<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SentimentAnalysis extends Model
{
    protected $fillable = [
        'analyzed_id',
        'analyzed_type',
        'sentiment_score',
        'sentiment_label',
        'confidence_score',
        'keywords',
        'summary'
    ];

    protected $casts = [
        'keywords' => 'array',
        'sentiment_score' => 'float',
        'confidence_score' => 'float'
    ];

    public function analyzed(): MorphTo
    {
        return $this->morphTo();
    }
} 