<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'post_id',
        'social_account_id',
        'platform_id',
        'scheduled_at',
        'posted_at',
        'status',
        'error_message',
        'platform_post_data'
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'posted_at' => 'datetime',
        'platform_post_data' => 'array'
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class);
    }
} 