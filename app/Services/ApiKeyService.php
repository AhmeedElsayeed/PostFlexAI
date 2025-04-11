<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class ApiKeyService
{
    protected $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Create a new API key for a user
     *
     * @param User $user
     * @param string $name
     * @param array $permissions
     * @param \DateTime|null $expiresAt
     * @return ApiKey
     */
    public function create(User $user, string $name, array $permissions = [], ?\DateTime $expiresAt = null)
    {
        $key = Str::random(64);
        $hashedKey = Hash::make($key);

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => $name,
            'key' => $hashedKey,
            'permissions' => $permissions,
            'expires_at' => $expiresAt,
        ]);

        $this->securityService->logEvent(
            'api_key_created',
            'success',
            $user,
            request(),
            ['name' => $name, 'permissions' => $permissions],
            'تم إنشاء مفتاح API جديد'
        );

        // Return the unhashed key only once
        $apiKey->plain_key = $key;
        return $apiKey;
    }

    /**
     * Validate an API key
     *
     * @param string $key
     * @return ApiKey|null
     */
    public function validate(string $key)
    {
        $apiKeys = ApiKey::where('is_active', true)->get();

        foreach ($apiKeys as $apiKey) {
            if (Hash::check($key, $apiKey->key)) {
                if (!$apiKey->isValid()) {
                    $this->securityService->logEvent(
                        'api_key_invalid',
                        'failed',
                        $apiKey->user,
                        request(),
                        ['reason' => $apiKey->isExpired() ? 'expired' : 'inactive'],
                        'محاولة استخدام مفتاح API غير صالح'
                    );
                    return null;
                }

                $apiKey->updateLastUsed();

                $this->securityService->logEvent(
                    'api_key_used',
                    'success',
                    $apiKey->user,
                    request(),
                    [],
                    'تم استخدام مفتاح API'
                );

                return $apiKey;
            }
        }

        $this->securityService->logEvent(
            'api_key_invalid',
            'failed',
            null,
            request(),
            ['reason' => 'invalid_key'],
            'محاولة استخدام مفتاح API غير صالح'
        );

        return null;
    }

    /**
     * Revoke an API key
     *
     * @param ApiKey $apiKey
     * @return bool
     */
    public function revoke(ApiKey $apiKey)
    {
        $apiKey->deactivate();

        $this->securityService->logEvent(
            'api_key_revoked',
            'success',
            $apiKey->user,
            request(),
            [],
            'تم إلغاء مفتاح API'
        );

        return true;
    }

    /**
     * Get all API keys for a user
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllForUser(User $user)
    {
        return $user->apiKeys()->get();
    }

    /**
     * Check if a user has permission for an action
     *
     * @param ApiKey $apiKey
     * @param string $permission
     * @return bool
     */
    public function hasPermission(ApiKey $apiKey, string $permission)
    {
        return in_array($permission, $apiKey->permissions ?? []);
    }

    /**
     * Extend the expiration of an API key
     *
     * @param ApiKey $apiKey
     * @param int $days
     * @return bool
     */
    public function extendExpiration(ApiKey $apiKey, int $days)
    {
        $apiKey->extendExpiration($days);

        $this->securityService->logEvent(
            'api_key_extended',
            'success',
            $apiKey->user,
            request(),
            ['days' => $days],
            'تم تمديد صلاحية مفتاح API'
        );

        return true;
    }

    /**
     * Set an API key to never expire
     *
     * @param ApiKey $apiKey
     * @return bool
     */
    public function setNeverExpire(ApiKey $apiKey)
    {
        $apiKey->setNeverExpire();

        $this->securityService->logEvent(
            'api_key_never_expire',
            'success',
            $apiKey->user,
            request(),
            [],
            'تم تعيين مفتاح API لعدم انتهاء الصلاحية'
        );

        return true;
    }
} 