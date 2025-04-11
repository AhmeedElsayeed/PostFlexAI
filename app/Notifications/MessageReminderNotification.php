<?php

namespace App\Notifications;

use App\Models\MessageReminder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class MessageReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected MessageReminder $reminder;

    public function __construct(MessageReminder $reminder)
    {
        $this->reminder = $reminder;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('تذكير برسالة تحتاج للرد')
            ->line('لديك رسالة تحتاج للرد:')
            ->line($this->reminder->message->content)
            ->line('ملاحظة: ' . ($this->reminder->note ?? 'لا توجد ملاحظات'))
            ->action('عرض الرسالة', url('/messages/' . $this->reminder->message_id));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'message_id' => $this->reminder->message_id,
            'reminder_id' => $this->reminder->id,
            'note' => $this->reminder->note,
            'content' => $this->reminder->message->content
        ];
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'message_id' => $this->reminder->message_id,
            'reminder_id' => $this->reminder->id,
            'note' => $this->reminder->note,
            'content' => $this->reminder->message->content
        ]);
    }
} 