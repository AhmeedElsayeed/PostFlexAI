<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecycledMedia extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_recycle_id',
        'original_media_id',
        'new_media_id',
        'action',
        'modifications'
    ];

    protected $casts = [
        'modifications' => 'array'
    ];

    /**
     * Get the content recycle that owns the recycled media.
     */
    public function contentRecycle(): BelongsTo
    {
        return $this->belongsTo(ContentRecycle::class);
    }

    /**
     * Get the original media.
     */
    public function originalMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'original_media_id');
    }

    /**
     * Get the new media.
     */
    public function newMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'new_media_id');
    }

    /**
     * Check if the media is reused.
     */
    public function isReused(): bool
    {
        return $this->action === 'reuse';
    }

    /**
     * Check if the media is replaced.
     */
    public function isReplaced(): bool
    {
        return $this->action === 'replace';
    }

    /**
     * Check if the media is modified.
     */
    public function isModified(): bool
    {
        return $this->action === 'modify';
    }

    /**
     * Get the modifications.
     */
    public function getModifications(): array
    {
        return $this->modifications ?? [];
    }

    /**
     * Get the action name.
     */
    public function getActionName(): string
    {
        $actions = [
            'reuse' => 'إعادة استخدام',
            'replace' => 'استبدال',
            'modify' => 'تعديل'
        ];

        return $actions[$this->action] ?? $this->action;
    }

    /**
     * Scope a query to only include reused media.
     */
    public function scopeReused($query)
    {
        return $query->where('action', 'reuse');
    }

    /**
     * Scope a query to only include replaced media.
     */
    public function scopeReplaced($query)
    {
        return $query->where('action', 'replace');
    }

    /**
     * Scope a query to only include modified media.
     */
    public function scopeModified($query)
    {
        return $query->where('action', 'modify');
    }
} 