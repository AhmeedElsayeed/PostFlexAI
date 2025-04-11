<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'key',
        'permissions',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the API key.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the API key is expired.
     */
    public function isExpired()
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the API key is valid.
     */
    public function isValid()
    {
        return $this->is_active && !$this->isExpired();
    }

    /**
     * Get the time remaining until expiration.
     */
    public function getTimeRemaining()
    {
        if (!$this->expires_at) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Extend the expiration date.
     */
    public function extendExpiration(int $days)
    {
        $this->expires_at = $this->expires_at ? $this->expires_at->addDays($days) : now()->addDays($days);
        $this->save();
    }

    /**
     * Set the API key to never expire.
     */
    public function setNeverExpire()
    {
        $this->expires_at = null;
        $this->save();
    }

    /**
     * Activate the API key.
     */
    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    /**
     * Deactivate the API key.
     */
    public function deactivate()
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * Update the last used timestamp.
     */
    public function updateLastUsed()
    {
        $this->last_used_at = now();
        $this->save();
    }
} 