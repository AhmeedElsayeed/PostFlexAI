<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentIdea extends Model
{
    protected $fillable = [
        'team_id',
        'title',
        'description',
        'platform',
        'goal',
        'theme',
        'suggestions',
        'is_ai_generated'
    ];

    protected $casts = [
        'suggestions' => 'array',
        'is_ai_generated' => 'boolean'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
} 