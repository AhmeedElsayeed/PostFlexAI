<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'plan_id',
        'status',
        'started_at',
        'ends_at',
        'renewal_date',
        'trial_ends_at',
        'payment_method',
        'total_paid',
        'is_auto_renew'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ends_at' => 'datetime',
        'renewal_date' => 'datetime',
        'trial_ends_at' => 'datetime',
        'total_paid' => 'decimal:2',
        'is_auto_renew' => 'boolean'
    ];

    /**
     * Get the team that owns the subscription.
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the plan that owns the subscription.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the invoices for the subscription.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at && $this->ends_at->isFuture();
    }

    /**
     * Check if the subscription is in trial.
     */
    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    /**
     * Check if the subscription is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    /**
     * Check if the subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->ends_at && $this->ends_at->isPast());
    }

    /**
     * Check if the subscription is auto-renewing.
     */
    public function isAutoRenewing(): bool
    {
        return $this->is_auto_renew && $this->isActive();
    }

    /**
     * Get the days remaining in the subscription.
     */
    public function getDaysRemaining(): int
    {
        if (!$this->ends_at) {
            return 0;
        }

        return max(0, Carbon::now()->diffInDays($this->ends_at, false));
    }

    /**
     * Get the days remaining in the trial.
     */
    public function getTrialDaysRemaining(): int
    {
        if (!$this->trial_ends_at) {
            return 0;
        }

        return max(0, Carbon::now()->diffInDays($this->trial_ends_at, false));
    }

    /**
     * Check if the subscription has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return $this->plan->hasFeature($feature);
    }

    /**
     * Scope a query to only include active subscriptions.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('ends_at', '>', now());
    }

    /**
     * Scope a query to only include trial subscriptions.
     */
    public function scopeTrial($query)
    {
        return $query->where('status', 'trial')
            ->where('trial_ends_at', '>', now());
    }

    /**
     * Scope a query to only include canceled subscriptions.
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }

    /**
     * Scope a query to only include expired subscriptions.
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere('ends_at', '<=', now());
        });
    }

    /**
     * Scope a query to only include auto-renewing subscriptions.
     */
    public function scopeAutoRenewing($query)
    {
        return $query->where('is_auto_renew', true)
            ->where('status', 'active')
            ->where('ends_at', '>', now());
    }
} 