<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\CalendarEventService;

class CalendarEventController extends Controller
{
    protected $calendarEventService;

    public function __construct(CalendarEventService $calendarEventService)
    {
        $this->calendarEventService = $calendarEventService;
    }

    public function index()
    {
        $events = $this->calendarEventService->getUpcomingEvents();
        return response()->json($events);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date',
            'type' => 'required|string|in:holiday,event,campaign',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'required_if:is_recurring,true|string',
            'reminder_days' => 'required|integer|min:1|max:30'
        ]);

        $event = $this->calendarEventService->createEvent($request->all());
        return response()->json($event);
    }

    public function getSuggestions()
    {
        $suggestions = $this->calendarEventService->getContentSuggestions();
        return response()->json($suggestions);
    }

    public function getUpcomingNotifications()
    {
        $notifications = $this->calendarEventService->getUpcomingNotifications();
        return response()->json($notifications);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'date' => 'sometimes|date',
            'type' => 'sometimes|string|in:holiday,event,campaign',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'required_if:is_recurring,true|string',
            'reminder_days' => 'sometimes|integer|min:1|max:30'
        ]);

        $event = $this->calendarEventService->updateEvent($id, $request->all());
        return response()->json($event);
    }

    public function destroy($id)
    {
        $this->calendarEventService->deleteEvent($id);
        return response()->json(['message' => 'Event deleted successfully']);
    }

    public function getEventContentIdeas($id)
    {
        $ideas = $this->calendarEventService->generateContentIdeas($id);
        return response()->json($ideas);
    }
} 