<?php

namespace App\Jobs;

use App\Models\SocialAccount;
use App\Models\AccountInsight;
use App\Services\FacebookService;
use App\Services\InstagramService;
use App\Services\TikTokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchAccountStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $account;

    public function __construct(SocialAccount $account)
    {
        $this->account = $account;
    }

    public function handle()
    {
        try {
            $platform = $this->account->platform;
            $stats = null;

            switch ($platform) {
                case 'facebook':
                    $stats = $this->fetchFacebookStats();
                    break;
                case 'instagram':
                    $stats = $this->fetchInstagramStats();
                    break;
                case 'tiktok':
                    $stats = $this->fetchTikTokStats();
                    break;
            }

            if ($stats) {
                $this->saveStats($stats);
            }
        } catch (\Exception $e) {
            Log::error("Error fetching account stats: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function fetchFacebookStats()
    {
        $service = new FacebookService($this->account->team);
        return $service->getAccountStats($this->account->platform_account_id);
    }

    protected function fetchInstagramStats()
    {
        $service = new InstagramService($this->account->team);
        return $service->getAccountStats($this->account->platform_account_id);
    }

    protected function fetchTikTokStats()
    {
        $service = new TikTokService($this->account->team);
        return $service->getAccountStats($this->account->platform_account_id);
    }

    protected function saveStats(array $stats)
    {
        AccountInsight::create([
            'social_account_id' => $this->account->id,
            'followers' => $stats['followers'] ?? 0,
            'posts_count' => $stats['posts_count'] ?? 0,
            'reach' => $stats['reach'] ?? null,
            'impressions' => $stats['impressions'] ?? null,
            'engagement_rate' => $stats['engagement_rate'] ?? null,
            'fetched_at' => now()
        ]);
    }
} 