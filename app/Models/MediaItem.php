<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class MediaItem extends Model
{
    protected $table = 'media_library';

    protected $fillable = [
        'team_id',
        'title',
        'description',
        'file_path',
        'file_type',
        'platform',
        'tags',
        'ai_labels',
        'is_starred',
        'file_size',
        'mime_type',
        'width',
        'height',
        'duration',
        'metadata'
    ];

    protected $casts = [
        'tags' => 'array',
        'ai_labels' => 'array',
        'is_starred' => 'boolean',
        'metadata' => 'array'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_media')
            ->withTimestamps();
    }

    // Scopes for filtering
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('file_type', $type);
    }

    public function scopeOfPlatform(Builder $query, string $platform): Builder
    {
        return $query->where('platform', $platform);
    }

    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeStarred(Builder $query): Builder
    {
        return $query->where('is_starred', true);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhereJsonContains('tags', $search)
              ->orWhereJsonContains('ai_labels', $search);
        });
    }

    // Helper methods
    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }

    public function getThumbnailUrl(): ?string
    {
        if ($this->file_type === 'image') {
            $path = str_replace('.', '_thumb.', $this->file_path);
            return Storage::url($path);
        }
        return null;
    }

    public function getFileSizeFormatted(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getDurationFormatted(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $hours = floor($this->duration / 3600);
        $minutes = floor(($this->duration % 3600) / 60);
        $seconds = $this->duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->file_type === 'video';
    }

    public function isGif(): bool
    {
        return $this->file_type === 'gif';
    }

    public function isPdf(): bool
    {
        return $this->file_type === 'pdf';
    }

    public function toggleStar(): bool
    {
        $this->is_starred = !$this->is_starred;
        return $this->save();
    }

    public function addTag(string $tag): bool
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
            return $this->save();
        }
        return false;
    }

    public function removeTag(string $tag): bool
    {
        $tags = $this->tags ?? [];
        if (($key = array_search($tag, $tags)) !== false) {
            unset($tags[$key]);
            $this->tags = array_values($tags);
            return $this->save();
        }
        return false;
    }
} 