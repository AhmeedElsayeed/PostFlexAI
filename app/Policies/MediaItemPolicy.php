<?php

namespace App\Policies;

use App\Models\MediaItem;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class MediaItemPolicy
{
    use HandlesAuthorization;

    public function view(User $user, MediaItem $mediaItem): bool
    {
        return $user->current_team_id === $mediaItem->team_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create media');
    }

    public function update(User $user, MediaItem $mediaItem): bool
    {
        return $user->current_team_id === $mediaItem->team_id && 
               $user->hasPermissionTo('edit media');
    }

    public function delete(User $user, MediaItem $mediaItem): bool
    {
        return $user->current_team_id === $mediaItem->team_id && 
               $user->hasPermissionTo('delete media');
    }
} 