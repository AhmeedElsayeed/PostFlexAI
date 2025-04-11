<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmartReply extends Model
{
    protected $fillable = [
        'content',
        'language',
        'platform',
        'context_type',
        'tone',
        'category',
        'user_id',
        'is_template',
        'usage_count',
        'success_rate'
    ];

    protected $casts = [
        'is_template' => 'boolean',
        'usage_count' => 'integer',
        'success_rate' => 'float'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 