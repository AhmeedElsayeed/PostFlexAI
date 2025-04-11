<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomSentimentWord extends Model
{
    use HasFactory;

    protected $fillable = [
        'word',
        'sentiment',
        'category',
        'description',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePositive($query)
    {
        return $query->where('sentiment', 'positive');
    }

    public function scopeNegative($query)
    {
        return $query->where('sentiment', 'negative');
    }
} 