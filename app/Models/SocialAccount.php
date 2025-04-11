<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'platform',
        'account_name',
        'account_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'is_active',
        'permissions',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'permissions' => 'array',
    ];

    protected $encrypted = [
        'access_token',
        'refresh_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setAccessTokenAttribute($value)
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function getAccessTokenAttribute($value)
    {
        return Crypt::decryptString($value);
    }

    public function setRefreshTokenAttribute($value)
    {
        if ($value) {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        }
    }

    public function getRefreshTokenAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public function isTokenExpired()
    {
        if (!$this->token_expires_at) {
            return false;
        }
        return now()->isAfter($this->token_expires_at);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }
} 