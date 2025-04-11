<?php

namespace App\Policies;

use App\Models\ContentRecycle;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContentRecyclePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any content recycles.
     *
     * @param User $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return true; // All authenticated users can view content recycles
    }

    /**
     * Determine whether the user can view the content recycle.
     *
     * @param User $user
     * @param ContentRecycle $contentRecycle
     * @return bool
     */
    public function view(User $user, ContentRecycle $contentRecycle)
    {
        return $user->current_team_id === $contentRecycle->post->team_id;
    }

    /**
     * Determine whether the user can create content recycles.
     *
     * @param User $user
     * @return bool
     */
    public function create(User $user)
    {
        return in_array($user->role, ['admin', 'content_manager', 'marketer']);
    }

    /**
     * Determine whether the user can update the content recycle.
     *
     * @param User $user
     * @param ContentRecycle $contentRecycle
     * @return bool
     */
    public function update(User $user, ContentRecycle $contentRecycle)
    {
        return $user->current_team_id === $contentRecycle->post->team_id &&
            in_array($user->role, ['admin', 'content_manager', 'marketer']);
    }

    /**
     * Determine whether the user can delete the content recycle.
     *
     * @param User $user
     * @param ContentRecycle $contentRecycle
     * @return bool
     */
    public function delete(User $user, ContentRecycle $contentRecycle)
    {
        return $user->current_team_id === $contentRecycle->post->team_id &&
            in_array($user->role, ['admin', 'content_manager']);
    }

    /**
     * Determine whether the user can approve content recycles.
     *
     * @param User $user
     * @param ContentRecycle $contentRecycle
     * @return bool
     */
    public function approve(User $user, ContentRecycle $contentRecycle)
    {
        return $user->current_team_id === $contentRecycle->post->team_id &&
            in_array($user->role, ['admin', 'content_manager']);
    }

    /**
     * Determine whether the user can schedule content recycles.
     *
     * @param User $user
     * @param ContentRecycle $contentRecycle
     * @return bool
     */
    public function schedule(User $user, ContentRecycle $contentRecycle)
    {
        return $user->current_team_id === $contentRecycle->post->team_id &&
            in_array($user->role, ['admin', 'content_manager', 'marketer']);
    }

    /**
     * Determine whether the user can view recycling statistics.
     *
     * @param User $user
     * @return bool
     */
    public function viewStats(User $user)
    {
        return true; // All authenticated users can view statistics
    }

    /**
     * Determine whether the user can compare performance.
     *
     * @param User $user
     * @param ContentRecycle $contentRecycle
     * @return bool
     */
    public function comparePerformance(User $user, ContentRecycle $contentRecycle)
    {
        return $user->current_team_id === $contentRecycle->post->team_id;
    }
} 