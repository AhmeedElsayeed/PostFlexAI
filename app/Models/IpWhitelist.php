<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpWhitelist extends Model
{
    use HasFactory;

    protected $table = 'ip_whitelist';

    protected $fillable = [
        'ip_address',
        'description',
        'is_active',
        'added_by',
        'expires_at'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime'
    ];

    /**
     * Get the user who added this IP address.
     */
    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Scope a query to only include active IP addresses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope a query to only include expired IP addresses.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if the IP address is active.
     */
    public function isActive(): bool
    {
        return $this->is_active && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if the IP address is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Get the time remaining until expiration.
     */
    public function getTimeRemaining(): ?string
    {
        if ($this->expires_at === null) {
            return null;
        }

        return $this->expires_at->diffForHumans();
    }

    /**
     * Activate the IP address.
     */
    public function activate(): bool
    {
        return $this->update(['is_active' => true]);
    }

    /**
     * Deactivate the IP address.
     */
    public function deactivate(): bool
    {
        return $this->update(['is_active' => false]);
    }

    /**
     * Extend the expiration date.
     */
    public function extendExpiration(int $days): bool
    {
        $newExpiration = $this->expires_at === null
            ? now()->addDays($days)
            : $this->expires_at->addDays($days);

        return $this->update(['expires_at' => $newExpiration]);
    }

    /**
     * Set the IP address to never expire.
     */
    public function setNeverExpire(): bool
    {
        return $this->update(['expires_at' => null]);
    }
} 