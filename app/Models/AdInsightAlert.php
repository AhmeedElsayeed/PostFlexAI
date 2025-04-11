<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdInsightAlert extends Model
{
    protected $fillable = [
        'ad_id',
        'ad_account_id',
        'type',
        'severity',
        'message',
        'metrics',
        'comparison_data',
        'is_resolved',
        'resolved_at'
    ];

    protected $casts = [
        'metrics' => 'array',
        'comparison_data' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime'
    ];

    public function ad(): BelongsTo
    {
        return $this->belongsTo(Ad::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }
} 