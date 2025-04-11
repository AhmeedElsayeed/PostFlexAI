<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'billing_cycle',
        'features',
        'max_teams',
        'max_users',
        'is_active'
    ];

    protected $casts = [
        'features' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'max_teams' => 'integer',
        'max_users' => 'integer'
    ];

    /**
     * Get the subscriptions for the plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get active subscriptions for the plan.
     */
    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'active');
    }

    /**
     * Get trial subscriptions for the plan.
     */
    public function trialSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'trial');
    }

    /**
     * Get canceled subscriptions for the plan.
     */
    public function canceledSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'canceled');
    }

    /**
     * Get expired subscriptions for the plan.
     */
    public function expiredSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('status', 'expired');
    }

    /**
     * Check if the plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get the yearly price for the plan.
     */
    public function getYearlyPrice(): float
    {
        if ($this->billing_cycle === 'yearly') {
            return $this->price;
        }
        
        // Apply a discount for yearly billing (e.g., 2 months free)
        return $this->price * 10;
    }

    /**
     * Get the monthly price for the plan.
     */
    public function getMonthlyPrice(): float
    {
        if ($this->billing_cycle === 'monthly') {
            return $this->price;
        }
        
        return $this->price / 12;
    }

    /**
     * Scope a query to only include active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include monthly plans.
     */
    public function scopeMonthly($query)
    {
        return $query->where('billing_cycle', 'monthly');
    }

    /**
     * Scope a query to only include yearly plans.
     */
    public function scopeYearly($query)
    {
        return $query->where('billing_cycle', 'yearly');
    }
} 