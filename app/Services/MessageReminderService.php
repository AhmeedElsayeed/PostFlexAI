<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageReminder;
use App\Notifications\MessageReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;

class MessageReminderService
{
    public function createReminder(Message $message, array $data): MessageReminder
    {
        return MessageReminder::create([
            'message_id' => $message->id,
            'user_id' => auth()->id(),
            'remind_at' => $data['remind_at'],
            'note' => $data['note'] ?? null,
            'status' => 'pending'
        ]);
    }

    public function processReminders(): void
    {
        $reminders = MessageReminder::with(['message', 'user'])
            ->where('status', 'pending')
            ->where('remind_at', '<=', Carbon::now())
            ->get();

        foreach ($reminders as $reminder) {
            $this->sendReminderNotification($reminder);
            $reminder->update(['status' => 'completed']);
        }
    }

    protected function sendReminderNotification(MessageReminder $reminder): void
    {
        Notification::send(
            $reminder->user,
            new MessageReminderNotification($reminder)
        );
    }

    public function getUpcomingReminders(int $userId, int $limit = 10): array
    {
        return MessageReminder::with(['message'])
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->where('remind_at', '>', Carbon::now())
            ->orderBy('remind_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }
} 