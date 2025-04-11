<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'priority',
        'subject',
        'message',
        'attachments',
        'metadata',
        'assigned_to',
        'resolved_at'
    ];

    protected $casts = [
        'attachments' => 'array',
        'metadata' => 'array',
        'resolved_at' => 'datetime'
    ];

    /**
     * Get the user who submitted the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin user assigned to the feedback.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope a query to only include feedback of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope a query to only include feedback with a specific status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include feedback with a specific priority.
     */
    public function scopeWithPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope a query to only include unresolved feedback.
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope a query to only include resolved feedback.
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * Scope a query to only include feedback assigned to a specific admin.
     */
    public function scopeAssignedTo($query, int $adminId)
    {
        return $query->where('assigned_to', $adminId);
    }

    /**
     * Scope a query to only include unassigned feedback.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    /**
     * Get the type name in Arabic.
     */
    public function getTypeName(): string
    {
        $types = [
            'bug' => 'خطأ',
            'feature_request' => 'طلب ميزة',
            'support' => 'دعم',
            'general' => 'عام',
            'other' => 'أخرى'
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * Get the status name in Arabic.
     */
    public function getStatusName(): string
    {
        $statuses = [
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد المعالجة',
            'resolved' => 'تم الحل',
            'closed' => 'مغلق',
            'other' => 'أخرى'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get the priority name in Arabic.
     */
    public function getPriorityName(): string
    {
        $priorities = [
            'low' => 'منخفض',
            'medium' => 'متوسط',
            'high' => 'عالي',
            'urgent' => 'عاجل',
            'other' => 'أخرى'
        ];

        return $priorities[$this->priority] ?? $this->priority;
    }

    /**
     * Mark the feedback as resolved.
     */
    public function markAsResolved(): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now()
        ]);
    }

    /**
     * Mark the feedback as in progress.
     */
    public function markAsInProgress(): bool
    {
        return $this->update([
            'status' => 'in_progress'
        ]);
    }

    /**
     * Mark the feedback as closed.
     */
    public function markAsClosed(): bool
    {
        return $this->update([
            'status' => 'closed'
        ]);
    }

    /**
     * Assign the feedback to an admin.
     */
    public function assignTo(User $admin): bool
    {
        return $this->update([
            'assigned_to' => $admin->id,
            'status' => 'in_progress'
        ]);
    }

    /**
     * Check if the feedback is resolved.
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved' || $this->status === 'closed';
    }

    /**
     * Check if the feedback is assigned.
     */
    public function isAssigned(): bool
    {
        return !is_null($this->assigned_to);
    }

    /**
     * Get the time elapsed since the feedback was created.
     */
    public function getTimeElapsed(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the time elapsed since the feedback was resolved.
     */
    public function getResolutionTime(): ?string
    {
        return $this->resolved_at ? $this->resolved_at->diffForHumans() : null;
    }
} 