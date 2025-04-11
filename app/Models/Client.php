<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'username',
        'platform',
        'profile_link',
        'phone',
        'email',
        'location',
        'status',
        'tags',
        'metadata',
        'last_interaction_at'
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'last_interaction_at' => 'datetime'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(InboxMessage::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'client_post_interactions')
                    ->withPivot('interaction_type', 'created_at')
                    ->withTimestamps();
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPlatform($query, $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByTag($query, $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('username', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%");
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'unresponsive')
                    ->whereNotNull('last_interaction_at')
                    ->where('last_interaction_at', '>=', now()->subDays(30));
    }

    public function updateLastInteraction()
    {
        $this->update(['last_interaction_at' => now()]);
    }

    public function addTag($tag)
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->update(['tags' => $tags]);
        }
    }

    public function removeTag($tag)
    {
        $tags = $this->tags ?? [];
        $tags = array_diff($tags, [$tag]);
        $this->update(['tags' => array_values($tags)]);
    }

    public function getInteractionStats()
    {
        return [
            'total_messages' => $this->messages()->count(),
            'total_posts' => $this->posts()->count(),
            'total_notes' => $this->notes()->count(),
            'last_interaction' => $this->last_interaction_at?->diffForHumans(),
            'interaction_frequency' => $this->calculateInteractionFrequency()
        ];
    }

    protected function calculateInteractionFrequency()
    {
        $messages = $this->messages()->orderBy('created_at')->get();
        if ($messages->isEmpty()) {
            return null;
        }

        $firstMessage = $messages->first();
        $lastMessage = $messages->last();
        $daysDiff = $firstMessage->created_at->diffInDays($lastMessage->created_at);
        
        if ($daysDiff === 0) {
            return 'daily';
        }

        $avgDaysBetweenMessages = $daysDiff / $messages->count();
        
        if ($avgDaysBetweenMessages <= 1) {
            return 'daily';
        } elseif ($avgDaysBetweenMessages <= 7) {
            return 'weekly';
        } elseif ($avgDaysBetweenMessages <= 30) {
            return 'monthly';
        } else {
            return 'rarely';
        }
    }
} 