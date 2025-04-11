<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';
    protected $description = 'Publish scheduled posts that are due';

    public function handle()
    {
        $now = Carbon::now();
        
        $posts = Post::where('status', 'scheduled')
            ->where('scheduled_at', '<=', $now)
            ->get();

        foreach ($posts as $post) {
            try {
                // TODO: Implement actual publishing logic for each platform
                // This is a placeholder for the actual implementation
                $post->update([
                    'status' => 'posted',
                    'posted_at' => $now
                ]);

                $this->info("Post {$post->id} published successfully");
            } catch (\Exception $e) {
                $post->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);

                $this->error("Failed to publish post {$post->id}: {$e->getMessage()}");
            }
        }

        return 0;
    }
} 