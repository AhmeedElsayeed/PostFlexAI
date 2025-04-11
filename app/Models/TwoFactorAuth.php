<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuth extends Model
{
    protected $fillable = [
        'user_id',
        'secret',
        'enabled',
        'backup_codes'
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'backup_codes' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 