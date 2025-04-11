<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\MessageLabel;
use App\Models\MessageNote;
use App\Services\MessageClassificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessageClassificationController extends Controller
{
    protected MessageClassificationService $classificationService;

    public function __construct(MessageClassificationService $classificationService)
    {
        $this->classificationService = $classificationService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = Message::with(['labels', 'notes'])
            ->when($request->label, function ($q) use ($request) {
                return $q->whereHas('labels', function ($q) use ($request) {
                    $q->where('label', $request->label);
                });
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->where('content', 'like', "%{$request->search}%");
            });

        $messages = $query->latest()->paginate(20);

        return response()->json([
            'data' => $messages,
            'available_labels' => $this->classificationService->getAvailableLabels()
        ]);
    }

    public function updateLabel(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'label' => 'required|string'
        ]);

        $message->labels()->create([
            'label' => $request->label,
            'source' => 'manual',
            'created_by' => auth()->id()
        ]);

        return response()->json([
            'message' => 'Label updated successfully'
        ]);
    }

    public function addNote(Request $request, Message $message): JsonResponse
    {
        $request->validate([
            'note' => 'required|string'
        ]);

        $message->notes()->create([
            'note' => $request->note,
            'added_by' => auth()->id()
        ]);

        return response()->json([
            'message' => 'Note added successfully'
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total_messages' => Message::count(),
            'unread_messages' => Message::whereDoesntHave('labels', function ($q) {
                $q->where('label', 'تم الرد والمتابعة');
            })->count(),
            'label_distribution' => MessageLabel::selectRaw('label, count(*) as count')
                ->groupBy('label')
                ->get(),
            'response_rate' => $this->calculateResponseRate()
        ];

        return response()->json($stats);
    }

    protected function calculateResponseRate(): float
    {
        $total = Message::count();
        if ($total === 0) return 0;

        $responded = Message::whereHas('labels', function ($q) {
            $q->where('label', 'تم الرد والمتابعة');
        })->count();

        return round(($responded / $total) * 100, 2);
    }
} 