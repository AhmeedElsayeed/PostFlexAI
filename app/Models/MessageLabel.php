<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLabel extends Model
{
    protected $fillable = [
        'message_id',
        'label',
        'source',
        'created_by'
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 