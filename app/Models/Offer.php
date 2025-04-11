<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'type',
        'value',
        'start_date',
        'end_date',
        'is_active',
        'target_personas',
        'target_segments',
        'terms_conditions',
        'max_usage_per_client',
        'total_usage_limit',
        'is_auto_generated',
        'ai_recommendations'
    ];

    protected $casts = [
        'target_personas' => 'array',
        'target_segments' => 'array',
        'terms_conditions' => 'array',
        'ai_recommendations' => 'array',
        'is_active' => 'boolean',
        'is_auto_generated' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'value' => 'decimal:2'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'coupons')
            ->withPivot('status', 'times_used', 'redeemed_at')
            ->withTimestamps();
    }

    public function personas(): BelongsToMany
    {
        return $this->belongsToMany(AudiencePersona::class, 'offer_persona')
            ->withTimestamps();
    }

    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(AudienceCluster::class, 'offer_segment')
            ->withTimestamps();
    }

    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        if (!$this->end_date) {
            return false;
        }

        return now()->gt($this->end_date);
    }

    public function getRemainingUsage(): ?int
    {
        if (!$this->total_usage_limit) {
            return null;
        }

        $used = $this->coupons()->where('status', 'used')->count();
        return max(0, $this->total_usage_limit - $used);
    }

    public function getActiveCouponsCount(): int
    {
        return $this->coupons()->where('status', 'active')->count();
    }

    public function getUsedCouponsCount(): int
    {
        return $this->coupons()->where('status', 'used')->count();
    }

    public function getExpiredCouponsCount(): int
    {
        return $this->coupons()->where('status', 'expired')->count();
    }

    public function getConversionRate(): float
    {
        $total = $this->coupons()->count();
        if ($total === 0) {
            return 0;
        }

        $used = $this->getUsedCouponsCount();
        return round(($used / $total) * 100, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('is_active', false)
                ->orWhere('end_date', '<', now());
        });
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByPersona($query, $personaId)
    {
        return $query->whereJsonContains('target_personas', $personaId);
    }

    public function scopeBySegment($query, $segmentId)
    {
        return $query->whereJsonContains('target_segments', $segmentId);
    }

    public function scopeAutoGenerated($query)
    {
        return $query->where('is_auto_generated', true);
    }

    public function scopeManual($query)
    {
        return $query->where('is_auto_generated', false);
    }
} 