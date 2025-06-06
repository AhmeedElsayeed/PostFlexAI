<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageReminder extends Model
{
    protected $fillable = [
        'message_id',
        'user_id',
        'remind_at',
        'note',
        'status'
    ];

    protected $casts = [
        'remind_at' => 'datetime'
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 