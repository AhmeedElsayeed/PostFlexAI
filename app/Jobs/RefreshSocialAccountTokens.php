<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Services\FacebookService;
use App\Services\InstagramService;
use App\Services\TikTokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshSocialAccountTokens implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $accounts = SocialAccount::where('status', 'active')->get();
        
        foreach ($accounts as $account) {
            try {
                $service = $this->getService($account->platform);
                if (!$service) continue;

                // Check token validity
                if (!$this->isTokenValid($account, $service)) {
                    // Refresh token
                    $newToken = $this->refreshToken($account, $service);
                    if ($newToken) {
                        $account->update([
                            'access_token' => $newToken,
                            'token_expires_at' => now()->addHours(2) // Update based on platform
                        ]);
                        Log::info("Token refreshed for account {$account->id}");
                    } else {
                        $account->update(['status' => 'error']);
                        Log::error("Failed to refresh token for account {$account->id}");
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error refreshing token for account {$account->id}: " . $e->getMessage());
                $account->update(['status' => 'error']);
            }
        }
    }

    private function getService($platform)
    {
        return match($platform) {
            'facebook' => new FacebookService(),
            'instagram' => new InstagramService(),
            'tiktok' => new TikTokService(),
            default => null
        };
    }

    private function isTokenValid($account, $service)
    {
        try {
            return $service->validateToken($account->access_token);
        } catch (\Exception $e) {
            Log::error("Token validation failed for account {$account->id}: " . $e->getMessage());
            return false;
        }
    }

    private function refreshToken($account, $service)
    {
        try {
            return $service->refreshToken($account->refresh_token);
        } catch (\Exception $e) {
            Log::error("Token refresh failed for account {$account->id}: " . $e->getMessage());
            return null;
        }
    }
} 