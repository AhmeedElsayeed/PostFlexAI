<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'icon',
        'supported_content_types',
        'supported_features',
        'is_active'
    ];

    protected $casts = [
        'supported_content_types' => 'array',
        'supported_features' => 'array',
        'is_active' => 'boolean'
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
} 