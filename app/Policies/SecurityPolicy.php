<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SecurityPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view security statistics.
     */
    public function viewStats(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view security logs.
     */
    public function viewLogs(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can manage IP whitelist.
     */
    public function manageIpWhitelist(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view suspicious activity.
     */
    public function viewSuspiciousActivity(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view device statistics.
     */
    public function viewDeviceStats(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can export security data.
     */
    public function exportData(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can manage security settings.
     */
    public function manageSettings(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view user security details.
     */
    public function viewUserSecurity(User $user, User $targetUser): bool
    {
        return $user->hasRole('admin') || $user->id === $targetUser->id;
    }

    /**
     * Determine whether the user can block/unblock users.
     */
    public function manageUserAccess(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view security audit logs.
     */
    public function viewAuditLogs(User $user): bool
    {
        return $user->hasRole('admin');
    }
} 