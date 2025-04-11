<?php

namespace App\Services;

use App\Models\IpWhitelist;
use App\Models\SecurityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecurityService
{
    /**
     * Log a security event.
     */
    public function logEvent(
        string $eventType,
        string $status,
        ?User $user = null,
        ?Request $request = null,
        ?array $metadata = null,
        ?string $description = null
    ): SecurityLog {
        $ipAddress = $request?->ip();
        $userAgent = $request?->userAgent();
        $deviceType = $this->getDeviceType($userAgent);
        $location = $this->getLocationFromIp($ipAddress);

        return SecurityLog::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'status' => $status,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'location' => $location,
            'metadata' => $metadata,
            'description' => $description
        ]);
    }

    /**
     * Check if an IP address is whitelisted.
     */
    public function isIpWhitelisted(?string $ipAddress): bool
    {
        if ($ipAddress === null) {
            return false;
        }

        return Cache::remember('ip_whitelist:' . $ipAddress, 300, function () use ($ipAddress) {
            return IpWhitelist::where('ip_address', $ipAddress)
                ->active()
                ->exists();
        });
    }

    /**
     * Add an IP address to the whitelist.
     */
    public function addIpToWhitelist(string $ipAddress, User $addedBy, ?string $description = null, ?int $expiresInDays = null): IpWhitelist
    {
        $expiresAt = $expiresInDays ? now()->addDays($expiresInDays) : null;

        $ipWhitelist = IpWhitelist::create([
            'ip_address' => $ipAddress,
            'description' => $description,
            'is_active' => true,
            'added_by' => $addedBy->id,
            'expires_at' => $expiresAt
        ]);

        Cache::forget('ip_whitelist:' . $ipAddress);

        return $ipWhitelist;
    }

    /**
     * Remove an IP address from the whitelist.
     */
    public function removeIpFromWhitelist(string $ipAddress): bool
    {
        $result = IpWhitelist::where('ip_address', $ipAddress)->delete();
        Cache::forget('ip_whitelist:' . $ipAddress);
        return $result;
    }

    /**
     * Check for suspicious activity.
     */
    public function checkSuspiciousActivity(User $user, Request $request): bool
    {
        $ipAddress = $request->ip();
        $userAgent = $request->userAgent();

        // Check for multiple failed login attempts
        $failedAttempts = SecurityLog::where('user_id', $user->id)
            ->where('event_type', 'login_attempt')
            ->where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($failedAttempts >= 5) {
            $this->logEvent(
                'suspicious_activity',
                'warning',
                $user,
                $request,
                ['reason' => 'multiple_failed_attempts', 'count' => $failedAttempts],
                'Multiple failed login attempts detected'
            );
            return true;
        }

        // Check for unusual IP address
        $usualIps = SecurityLog::where('user_id', $user->id)
            ->where('event_type', 'login_attempt')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDays(30))
            ->pluck('ip_address')
            ->unique()
            ->toArray();

        if (!in_array($ipAddress, $usualIps) && !empty($usualIps)) {
            $this->logEvent(
                'suspicious_activity',
                'warning',
                $user,
                $request,
                ['reason' => 'unusual_ip', 'usual_ips' => $usualIps],
                'Login attempt from unusual IP address'
            );
            return true;
        }

        // Check for unusual user agent
        $usualUserAgents = SecurityLog::where('user_id', $user->id)
            ->where('event_type', 'login_attempt')
            ->where('status', 'success')
            ->where('created_at', '>=', now()->subDays(30))
            ->pluck('user_agent')
            ->unique()
            ->toArray();

        if (!in_array($userAgent, $usualUserAgents) && !empty($usualUserAgents)) {
            $this->logEvent(
                'suspicious_activity',
                'warning',
                $user,
                $request,
                ['reason' => 'unusual_user_agent', 'usual_user_agents' => $usualUserAgents],
                'Login attempt from unusual device'
            );
            return true;
        }

        return false;
    }

    /**
     * Get security statistics.
     */
    public function getSecurityStats(): array
    {
        return Cache::remember('security_stats', 300, function () {
            $now = now();
            $lastHour = $now->copy()->subHour();
            $lastDay = $now->copy()->subDay();
            $lastWeek = $now->copy()->subWeek();
            $lastMonth = $now->copy()->subMonth();

            return [
                'login_attempts' => [
                    'total' => SecurityLog::where('event_type', 'login_attempt')->count(),
                    'successful' => SecurityLog::where('event_type', 'login_attempt')->where('status', 'success')->count(),
                    'failed' => SecurityLog::where('event_type', 'login_attempt')->where('status', 'failed')->count(),
                    'blocked' => SecurityLog::where('event_type', 'login_attempt')->where('status', 'blocked')->count(),
                    'last_hour' => SecurityLog::where('event_type', 'login_attempt')->where('created_at', '>=', $lastHour)->count(),
                    'last_day' => SecurityLog::where('event_type', 'login_attempt')->where('created_at', '>=', $lastDay)->count(),
                    'last_week' => SecurityLog::where('event_type', 'login_attempt')->where('created_at', '>=', $lastWeek)->count(),
                    'last_month' => SecurityLog::where('event_type', 'login_attempt')->where('created_at', '>=', $lastMonth)->count(),
                ],
                'suspicious_activities' => [
                    'total' => SecurityLog::where('event_type', 'suspicious_activity')->count(),
                    'last_hour' => SecurityLog::where('event_type', 'suspicious_activity')->where('created_at', '>=', $lastHour)->count(),
                    'last_day' => SecurityLog::where('event_type', 'suspicious_activity')->where('created_at', '>=', $lastDay)->count(),
                    'last_week' => SecurityLog::where('event_type', 'suspicious_activity')->where('created_at', '>=', $lastWeek)->count(),
                    'last_month' => SecurityLog::where('event_type', 'suspicious_activity')->where('created_at', '>=', $lastMonth)->count(),
                ],
                'ip_whitelist' => [
                    'total' => IpWhitelist::count(),
                    'active' => IpWhitelist::active()->count(),
                    'expired' => IpWhitelist::expired()->count(),
                ],
                'device_types' => [
                    'mobile' => SecurityLog::where('device_type', 'mobile')->count(),
                    'tablet' => SecurityLog::where('device_type', 'tablet')->count(),
                    'desktop' => SecurityLog::where('device_type', 'desktop')->count(),
                    'other' => SecurityLog::where('device_type', 'other')->count(),
                ],
            ];
        });
    }

    /**
     * Get device type from user agent.
     */
    protected function getDeviceType(?string $userAgent): string
    {
        if ($userAgent === null) {
            return 'other';
        }

        $userAgent = strtolower($userAgent);

        if (preg_match('/(android|iphone|ipad|ipod|mobile)/', $userAgent)) {
            if (preg_match('/(tablet|ipad)/', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }

        return 'desktop';
    }

    /**
     * Get location from IP address.
     */
    protected function getLocationFromIp(?string $ipAddress): ?string
    {
        if ($ipAddress === null) {
            return null;
        }

        // This is a placeholder. In a real application, you would use a geolocation service.
        // For example, you could use the MaxMind GeoIP2 database or a service like ipstack.com.
        return null;
    }
} 