<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdInsightRecommendation extends Model
{
    protected $fillable = [
        'ad_id',
        'ad_account_id',
        'type',
        'priority',
        'title',
        'description',
        'metrics_impact',
        'implementation_steps',
        'is_implemented',
        'implemented_at',
        'results'
    ];

    protected $casts = [
        'metrics_impact' => 'array',
        'implementation_steps' => 'array',
        'is_implemented' => 'boolean',
        'implemented_at' => 'datetime',
        'results' => 'array'
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