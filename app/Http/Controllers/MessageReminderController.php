<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Services\MessageReminderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageReminderController extends Controller
{
    protected MessageReminderService $reminderService;

    public function __construct(MessageReminderService $reminderService)
    {
        $this->reminderService = $reminderService;
    }

    public function store(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'remind_at' => 'required|date|after:now',
            'note' => 'nullable|string|max:500'
        ]);

        $reminder = $this->reminderService->createReminder($message, $request->all());

        return response()->json([
            'message' => 'تم إنشاء التذكير بنجاح',
            'data' => $reminder
        ], 201);
    }

    public function index(): JsonResponse
    {
        $reminders = $this->reminderService->getUpcomingReminders(auth()->id());

        return response()->json([
            'data' => $reminders
        ]);
    }

    public function destroy(Message $message): JsonResponse
    {
        $message->reminders()
            ->where('user_id', auth()->id())
            ->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        return response()->json([
            'message' => 'تم إلغاء التذكير بنجاح'
        ]);
    }
} 
 