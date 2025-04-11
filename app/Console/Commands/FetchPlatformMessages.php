<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Services\FacebookService;
use App\Services\InstagramService;
use App\Services\TikTokService;
use Illuminate\Console\Command;

class FetchPlatformMessages extends Command
{
    protected $signature = 'fetch:platform-messages';
    protected $description = 'Fetch messages and comments from social media platforms';

    public function handle()
    {
        $teams = Team::all();

        foreach ($teams as $team) {
            $this->info("Processing team: {$team->name}");

            // Fetch Facebook messages
            $this->fetchFacebookMessages($team);

            // Fetch Instagram messages
            $this->fetchInstagramMessages($team);

            // Fetch TikTok messages
            $this->fetchTikTokMessages($team);
        }

        $this->info('Platform messages fetch completed');
    }

    protected function fetchFacebookMessages(Team $team)
    {
        try {
            $facebookService = new FacebookService($team);
            $messages = $facebookService->fetchMessages();
            
            foreach ($messages as $message) {
                $this->processMessage($team, 'facebook', $message);
            }
        } catch (\Exception $e) {
            $this->error("Facebook fetch error: {$e->getMessage()}");
        }
    }

    protected function fetchInstagramMessages(Team $team)
    {
        try {
            $instagramService = new InstagramService($team);
            $messages = $instagramService->fetchMessages();
            
            foreach ($messages as $message) {
                $this->processMessage($team, 'instagram', $message);
            }
        } catch (\Exception $e) {
            $this->error("Instagram fetch error: {$e->getMessage()}");
        }
    }

    protected function fetchTikTokMessages(Team $team)
    {
        try {
            $tiktokService = new TikTokService($team);
            $messages = $tiktokService->fetchMessages();
            
            foreach ($messages as $message) {
                $this->processMessage($team, 'tiktok', $message);
            }
        } catch (\Exception $e) {
            $this->error("TikTok fetch error: {$e->getMessage()}");
        }
    }

    protected function processMessage(Team $team, string $platform, array $message)
    {
        // Check if message already exists
        $existingMessage = \App\Models\InboxMessage::where('team_id', $team->id)
            ->where('platform', $platform)
            ->where('message_id', $message['id'])
            ->first();

        if ($existingMessage) {
            return;
        }

        // Create new message
        $inboxMessage = \App\Models\InboxMessage::create([
            'team_id' => $team->id,
            'platform' => $platform,
            'message_id' => $message['id'],
            'sender_name' => $message['sender_name'],
            'message_text' => $message['text'],
            'type' => $message['type'],
            'received_at' => now()
        ]);

        // Check for auto-replies
        $this->checkAutoReplies($inboxMessage);
    }

    protected function checkAutoReplies(\App\Models\InboxMessage $message)
    {
        $autoReplies = \App\Models\AutoReply::where('team_id', $message->team_id)
            ->where('platform', $message->platform)
            ->get();

        foreach ($autoReplies as $autoReply) {
            if (str_contains(strtolower($message->message_text), strtolower($autoReply->trigger_keyword))) {
                // Send auto-reply
                $this->sendAutoReply($message, $autoReply);
                break;
            }
        }
    }

    protected function sendAutoReply(\App\Models\InboxMessage $message, \App\Models\AutoReply $autoReply)
    {
        try {
            // TODO: Implement actual reply sending logic for each platform
            $message->update([
                'status' => 'replied',
                'is_automated' => true
            ]);
        } catch (\Exception $e) {
            $this->error("Auto-reply error: {$e->getMessage()}");
        }
    }
} 