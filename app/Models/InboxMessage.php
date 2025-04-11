<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboxMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'user_id',
        'platform',
        'message_id',
        'sender_name',
        'message_text',
        'type',
        'status',
        'is_automated',
        'received_at'
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'is_automated' => 'boolean'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isNew()
    {
        return $this->status === 'new';
    }

    public function isRead()
    {
        return $this->status === 'read';
    }

    public function isReplied()
    {
        return $this->status === 'replied';
    }

    public function isArchived()
    {
        return $this->status === 'archived';
    }
} 