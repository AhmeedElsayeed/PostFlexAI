<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\PostInsight;
use App\Services\FacebookService;
use App\Services\InstagramService;
use App\Services\TikTokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchPostInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $post;

    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    public function handle()
    {
        try {
            $platform = $this->post->platform;
            $insights = null;

            switch ($platform) {
                case 'facebook':
                    $insights = $this->fetchFacebookInsights();
                    break;
                case 'instagram':
                    $insights = $this->fetchInstagramInsights();
                    break;
                case 'tiktok':
                    $insights = $this->fetchTikTokInsights();
                    break;
            }

            if ($insights) {
                $this->saveInsights($insights);
            }
        } catch (\Exception $e) {
            Log::error("Error fetching post insights: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function fetchFacebookInsights()
    {
        $service = new FacebookService($this->post->team);
        return $service->getPostInsights($this->post->platform_post_id);
    }

    protected function fetchInstagramInsights()
    {
        $service = new InstagramService($this->post->team);
        return $service->getPostInsights($this->post->platform_post_id);
    }

    protected function fetchTikTokInsights()
    {
        $service = new TikTokService($this->post->team);
        return $service->getPostInsights($this->post->platform_post_id);
    }

    protected function saveInsights(array $insights)
    {
        PostInsight::create([
            'post_id' => $this->post->id,
            'platform' => $this->post->platform,
            'likes' => $insights['likes'] ?? 0,
            'comments' => $insights['comments'] ?? 0,
            'shares' => $insights['shares'] ?? 0,
            'views' => $insights['views'] ?? null,
            'saves' => $insights['saves'] ?? null,
            'engagement_rate' => $insights['engagement_rate'] ?? null,
            'fetched_at' => now()
        ]);
    }
} 