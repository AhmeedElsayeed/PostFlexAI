<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageNote extends Model
{
    protected $fillable = [
        'message_id',
        'note',
        'added_by'
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    public function adder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
} 