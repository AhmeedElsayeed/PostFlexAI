<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class PasswordReset extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function generateToken(User $user): self
    {
        return self::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addHours(24),
            'used' => false
        ]);
    }

    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }
} 