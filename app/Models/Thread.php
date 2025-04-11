<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Thread extends Model
{
    protected $fillable = [
        'title',
        'description',
        'user_id',
        'platform',
        'status',
        'scheduled_at',
        'published_at'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime'
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 