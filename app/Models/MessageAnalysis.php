<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_id',
        'message_id',
        'sentiment',
        'intent',
        'priority',
        'keywords',
        'success',
        'response_time',
        'category',
        'success_rate'
    ];

    protected $casts = [
        'keywords' => 'array',
        'success' => 'boolean',
        'success_rate' => 'float'
    ];

    public function template()
    {
        return $this->belongsTo(SmartReplyTemplate::class, 'template_id');
    }

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id');
    }
} 