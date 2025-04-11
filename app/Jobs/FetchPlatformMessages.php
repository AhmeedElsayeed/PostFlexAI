<?php

namespace App\Jobs;

use App\Models\InboxMessage;
use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchPlatformMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $team;
    protected $platform;

    public function __construct(Team $team, string $platform)
    {
        $this->team = $team;
        $this->platform = $platform;
    }

    public function handle()
    {
        // TODO: Implement actual fetching logic for each platform
        // This is a placeholder for the actual implementation
        $messages = []; // Fetch messages from platform API

        foreach ($messages as $message) {
            InboxMessage::updateOrCreate(
                [
                    'team_id' => $this->team->id,
                    'platform' => $this->platform,
                    'message_id' => $message['id']
                ],
                [
                    'sender_name' => $message['sender_name'],
                    'message_text' => $message['text'],
                    'type' => $message['type'],
                    'received_at' => now()
                ]
            );

            // Check for auto-replies
            $this->checkAutoReplies($message);
        }
    }

    protected function checkAutoReplies($message)
    {
        $autoReplies = $this->team->autoReplies()
            ->where('platform', $this->platform)
            ->get();

        foreach ($autoReplies as $autoReply) {
            if (str_contains(strtolower($message['text']), strtolower($autoReply->trigger_keyword))) {
                // TODO: Implement actual reply sending logic
                // This is a placeholder for the actual implementation
                InboxMessage::where('message_id', $message['id'])
                    ->update([
                        'status' => 'replied',
                        'is_automated' => true
                    ]);
            }
        }
    }
} 