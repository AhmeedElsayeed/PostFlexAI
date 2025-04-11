<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReplyTemplateUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'reply_template_id',
        'user_id',
        'inbox_message_id',
        'platform',
        'customized_data'
    ];

    protected $casts = [
        'customized_data' => 'array'
    ];

    public function template()
    {
        return $this->belongsTo(ReplyTemplate::class, 'reply_template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function message()
    {
        return $this->belongsTo(InboxMessage::class, 'inbox_message_id');
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeWithinPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
} 