<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ReplyTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'title',
        'content',
        'tags',
        'is_active',
        'is_global',
        'usage_count',
        'is_starred',
        'tone',
        'metadata'
    ];

    protected $casts = [
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_global' => 'boolean',
        'usage_count' => 'integer',
        'is_starred' => 'boolean',
        'metadata' => 'array'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function usages()
    {
        return $this->hasMany(ReplyTemplateUsage::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'reply_template_usage')
                    ->withPivot('customized_data', 'platform')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->belongsToMany(InboxMessage::class, 'reply_template_usage')
                    ->withPivot('customized_data', 'platform')
                    ->withTimestamps();
    }

    public function customize(array $data): string
    {
        $content = $this->content;
        
        foreach ($data as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        return $content;
    }

    public function incrementUsage(User $user, ?InboxMessage $message = null, ?string $platform = null, ?array $customizedData = null)
    {
        $this->increment('usage_count');
        
        return $this->usages()->create([
            'user_id' => $user->id,
            'inbox_message_id' => $message?->id,
            'platform' => $platform,
            'customized_data' => $customizedData
        ]);
    }

    public function toggleStar()
    {
        $this->is_starred = !$this->is_starred;
        $this->save();
        
        return $this;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeGlobal($query)
    {
        return $query->where('is_global', true);
    }

    public function scopeStarred($query)
    {
        return $query->where('is_starred', true);
    }

    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeByTone($query, $tone)
    {
        return $query->where('tone', $tone);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('content', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search);
        });
    }

    public function getMostUsedTagsAttribute()
    {
        return $this->tags ? array_slice($this->tags, 0, 5) : [];
    }

    public function getUsageStatsAttribute()
    {
        return [
            'total' => $this->usage_count,
            'by_platform' => $this->usages()->selectRaw('platform, count(*) as count')
                                          ->groupBy('platform')
                                          ->get()
                                          ->pluck('count', 'platform')
                                          ->toArray(),
            'by_user' => $this->usages()->selectRaw('user_id, count(*) as count')
                                       ->groupBy('user_id')
                                       ->with('user:id,name')
                                       ->get()
                                       ->pluck('count', 'user.name')
                                       ->toArray()
        ];
    }
} 