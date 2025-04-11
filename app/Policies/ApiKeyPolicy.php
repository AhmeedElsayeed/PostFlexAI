<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    /**
     * Determine whether the user can view any API keys.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the API key.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ApiKey  $apiKey
     * @return bool
     */
    public function view(User $user, ApiKey $apiKey)
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can create API keys.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the API key.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ApiKey  $apiKey
     * @return bool
     */
    public function update(User $user, ApiKey $apiKey)
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can delete the API key.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ApiKey  $apiKey
     * @return bool
     */
    public function delete(User $user, ApiKey $apiKey)
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can restore the API key.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ApiKey  $apiKey
     * @return bool
     */
    public function restore(User $user, ApiKey $apiKey)
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can permanently delete the API key.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ApiKey  $apiKey
     * @return bool
     */
    public function forceDelete(User $user, ApiKey $apiKey)
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can extend the API key's expiration.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ApiKey  $apiKey
     * @return bool
     */
    public function extend(User $user, ApiKey $apiKey)
    {
        return $user->id === $apiKey->user_id;
    }

    /**
     * Determine whether the user can set the API key to never expire.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ApiKey  $apiKey
     * @return bool
     */
    public function setNeverExpire(User $user, ApiKey $apiKey)
    {
        return $user->id === $apiKey->user_id;
    }
} 