<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'amount',
        'status',
        'issued_at',
        'paid_at',
        'payment_reference'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime'
    ];

    /**
     * Get the subscription that owns the invoice.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Check if the invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Check if the invoice is unpaid.
     */
    public function isUnpaid(): bool
    {
        return $this->status === 'unpaid';
    }

    /**
     * Mark the invoice as paid.
     */
    public function markAsPaid(string $paymentReference = null): bool
    {
        $this->status = 'paid';
        $this->paid_at = now();
        $this->payment_reference = $paymentReference;
        
        return $this->save();
    }

    /**
     * Mark the invoice as unpaid.
     */
    public function markAsUnpaid(): bool
    {
        $this->status = 'unpaid';
        $this->paid_at = null;
        
        return $this->save();
    }

    /**
     * Get the formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return number_format($this->amount, 2);
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope a query to only include unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('status', 'unpaid');
    }

    /**
     * Scope a query to only include invoices issued in the last X days.
     */
    public function scopeIssuedInLastDays($query, int $days)
    {
        return $query->where('issued_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to only include invoices paid in the last X days.
     */
    public function scopePaidInLastDays($query, int $days)
    {
        return $query->where('paid_at', '>=', now()->subDays($days));
    }
} 