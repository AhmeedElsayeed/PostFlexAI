<?php

namespace App\Services;

use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LoginAttemptService
{
    protected $securityService;
    protected $maxAttempts = 5;
    protected $lockoutTime = 3600; // 1 hour in seconds

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Check if a login attempt is suspicious.
     *
     * @param User $user
     * @param Request $request
     * @return bool
     */
    public function isSuspicious(User $user, Request $request): bool
    {
        // Check if the user is locked out
        if ($this->isLockedOut($user)) {
            return true;
        }

        // Check for multiple failed attempts
        if ($this->hasTooManyFailedAttempts($user)) {
            $this->lockout($user);
            return true;
        }

        // Check for unusual IP address
        if ($this->isUnusualIp($user, $request)) {
            return true;
        }

        // Check for unusual user agent
        if ($this->isUnusualUserAgent($user, $request)) {
            return true;
        }

        // Check for unusual time
        if ($this->isUnusualTime($user)) {
            return true;
        }

        return false;
    }

    /**
     * Record a login attempt.
     *
     * @param User $user
     * @param Request $request
     * @param bool $success
     * @return void
     */
    public function recordAttempt(User $user, Request $request, bool $success): void
    {
        $status = $success ? 'success' : 'failed';
        
        $this->securityService->logEvent(
            'login_attempt',
            $status,
            $user,
            $request,
            [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'success' => $success
            ]
        );

        if (!$success) {
            $this->incrementFailedAttempts($user);
        } else {
            $this->resetFailedAttempts($user);
        }
    }

    /**
     * Check if a user is locked out.
     *
     * @param User $user
     * @return bool
     */
    protected function isLockedOut(User $user): bool
    {
        return Cache::has('login_lockout:' . $user->id);
    }

    /**
     * Lock out a user.
     *
     * @param User $user
     * @return void
     */
    protected function lockout(User $user): void
    {
        Cache::put('login_lockout:' . $user->id, true, $this->lockoutTime);
        
        Log::warning('User locked out due to too many failed login attempts', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    /**
     * Check if a user has too many failed attempts.
     *
     * @param User $user
     * @return bool
     */
    protected function hasTooManyFailedAttempts(User $user): bool
    {
        $attempts = Cache::get('login_attempts:' . $user->id, 0);
        return $attempts >= $this->maxAttempts;
    }

    /**
     * Increment failed attempts for a user.
     *
     * @param User $user
     * @return void
     */
    protected function incrementFailedAttempts(User $user): void
    {
        $attempts = Cache::get('login_attempts:' . $user->id, 0);
        Cache::put('login_attempts:' . $user->id, $attempts + 1, $this->lockoutTime);
    }

    /**
     * Reset failed attempts for a user.
     *
     * @param User $user
     * @return void
     */
    protected function resetFailedAttempts(User $user): void
    {
        Cache::forget('login_attempts:' . $user->id);
        Cache::forget('login_lockout:' . $user->id);
    }

    /**
     * Check if the IP address is unusual for the user.
     *
     * @param User $user
     * @param Request $request
     * @return bool
     */
    protected function isUnusualIp(User $user, Request $request): bool
    {
        $usualIps = SecurityLog::where('user_id', $user->id)
            ->where('event_type', 'login_attempt')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDays(30))
            ->pluck('ip_address')
            ->unique()
            ->toArray();

        return !in_array($request->ip(), $usualIps) && !empty($usualIps);
    }

    /**
     * Check if the user agent is unusual for the user.
     *
     * @param User $user
     * @param Request $request
     * @return bool
     */
    protected function isUnusualUserAgent(User $user, Request $request): bool
    {
        $usualUserAgents = SecurityLog::where('user_id', $user->id)
            ->where('event_type', 'login_attempt')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDays(30))
            ->pluck('user_agent')
            ->unique()
            ->toArray();

        return !in_array($request->userAgent(), $usualUserAgents) && !empty($usualUserAgents);
    }

    /**
     * Check if the login time is unusual for the user.
     *
     * @param User $user
     * @return bool
     */
    protected function isUnusualTime(User $user): bool
    {
        $usualHours = SecurityLog::where('user_id', $user->id)
            ->where('event_type', 'login_attempt')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDays(30))
            ->get()
            ->map(function ($log) {
                return $log->created_at->hour;
            })
            ->toArray();

        $currentHour = now()->hour;
        
        // If the user has logged in at least 5 times, check if the current hour is unusual
        if (count($usualHours) >= 5) {
            $hourCounts = array_count_values($usualHours);
            $mostCommonHour = array_search(max($hourCounts), $hourCounts);
            
            // If the current hour is more than 6 hours away from the most common hour, it's unusual
            return abs($currentHour - $mostCommonHour) > 6;
        }

        return false;
    }
} 