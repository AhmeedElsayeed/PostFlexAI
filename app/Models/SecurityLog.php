<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'event_type',
        'status',
        'ip_address',
        'user_agent',
        'device_type',
        'location',
        'metadata',
        'description'
    ];

    protected $casts = [
        'metadata' => 'array'
    ];

    /**
     * Get the user associated with the security log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include logs of a specific event type.
     */
    public function scopeOfType($query, string $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope a query to only include logs with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include logs from a specific IP address.
     */
    public function scopeFromIp($query, string $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope a query to only include logs from a specific device type.
     */
    public function scopeFromDevice($query, string $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Get the event type name in Arabic.
     */
    public function getEventTypeName(): string
    {
        $types = [
            'login_attempt' => 'محاولة تسجيل دخول',
            'api_usage' => 'استخدام API',
            'suspicious_activity' => 'نشاط مشبوه',
            'ip_blocked' => 'حظر عنوان IP',
            'admin_action' => 'إجراء إداري',
            'other' => 'أخرى'
        ];

        return $types[$this->event_type] ?? $this->event_type;
    }

    /**
     * Get the status name in Arabic.
     */
    public function getStatusName(): string
    {
        $statuses = [
            'success' => 'نجاح',
            'failed' => 'فشل',
            'blocked' => 'محظور',
            'warning' => 'تحذير',
            'other' => 'أخرى'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get the device type name in Arabic.
     */
    public function getDeviceTypeName(): string
    {
        $types = [
            'mobile' => 'هاتف محمول',
            'tablet' => 'جهاز لوحي',
            'desktop' => 'حاسوب مكتبي',
            'other' => 'أخرى'
        ];

        return $types[$this->device_type] ?? $this->device_type;
    }

    /**
     * Check if the log is for a successful event.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the log is for a failed event.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if the log is for a blocked event.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Get the time elapsed since the event occurred.
     */
    public function getTimeElapsed(): string
    {
        return $this->created_at->diffForHumans();
    }
} 