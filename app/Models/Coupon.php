<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'code',
        'status',
        'max_usage',
        'times_used',
        'client_id',
        'redeemed_at',
        'usage_history'
    ];

    protected $casts = [
        'usage_history' => 'array',
        'redeemed_at' => 'datetime',
        'times_used' => 'integer',
        'max_usage' => 'integer'
    ];

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->offer->isActive() && 
               (!$this->max_usage || $this->times_used < $this->max_usage);
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->offer->isExpired();
    }

    public function isUsed(): bool
    {
        return $this->status === 'used';
    }

    public function canBeUsed(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($this->max_usage && $this->times_used >= $this->max_usage) {
            return false;
        }

        return true;
    }

    public function markAsUsed(): bool
    {
        if (!$this->canBeUsed()) {
            return false;
        }

        $this->status = 'used';
        $this->times_used++;
        $this->redeemed_at = now();
        
        // Add to usage history
        $history = $this->usage_history ?? [];
        $history[] = [
            'timestamp' => now()->toIso8601String(),
            'client_id' => $this->client_id,
            'status' => 'used'
        ];
        $this->usage_history = $history;

        return $this->save();
    }

    public function markAsExpired(): bool
    {
        if ($this->status === 'used') {
            return false;
        }

        $this->status = 'expired';
        
        // Add to usage history
        $history = $this->usage_history ?? [];
        $history[] = [
            'timestamp' => now()->toIso8601String(),
            'client_id' => $this->client_id,
            'status' => 'expired'
        ];
        $this->usage_history = $history;

        return $this->save();
    }

    public function getRemainingUsage(): ?int
    {
        if (!$this->max_usage) {
            return null;
        }

        return max(0, $this->max_usage - $this->times_used);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeByClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeByOffer($query, $offerId)
    {
        return $query->where('offer_id', $offerId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'active')
            ->whereHas('offer', function ($q) {
                $q->where('is_active', true)
                    ->where(function ($sq) {
                        $sq->whereNull('start_date')
                            ->orWhere('start_date', '<=', now());
                    })
                    ->where(function ($sq) {
                        $sq->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    });
            })
            ->where(function ($q) {
                $q->whereNull('max_usage')
                    ->orWhereRaw('times_used < max_usage');
            });
    }
} 