<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceAlert extends Model
{
    protected $fillable = [
        'team_id',
        'social_account_id',
        'type',
        'severity',
        'message',
        'details',
        'read_at',
        'resolved_at'
    ];

    protected $casts = [
        'details' => 'array',
        'read_at' => 'datetime',
        'resolved_at' => 'datetime'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function markAsRead(): bool
    {
        return $this->update(['read_at' => now()]);
    }

    public function markAsResolved(): bool
    {
        return $this->update(['resolved_at' => now()]);
    }

    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function isResolved(): bool
    {
        return !is_null($this->resolved_at);
    }
} 