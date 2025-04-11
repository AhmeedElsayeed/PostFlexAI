<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'note',
        'type',
        'is_private'
    ];

    protected $casts = [
        'is_private' => 'boolean'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_private', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
} 