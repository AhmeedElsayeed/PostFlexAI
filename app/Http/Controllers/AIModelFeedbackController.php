<?php

namespace App\Http\Controllers;

use App\Models\AIModelFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AIModelFeedbackController extends Controller
{
    public function index(Request $request)
    {
        $query = AIModelFeedback::where('team_id', Auth::user()->current_team_id);

        if ($request->has('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->has('feedback_type')) {
            $query->where('feedback_type', $request->feedback_type);
        }

        if ($request->has('is_resolved')) {
            $query->where('is_resolved', $request->boolean('is_resolved'));
        }

        $feedback = $query->latest()->paginate(10);

        return response()->json([
            'data' => $feedback,
            'meta' => [
                'total' => $feedback->total(),
                'per_page' => $feedback->perPage(),
                'current_page' => $feedback->currentPage(),
                'last_page' => $feedback->lastPage()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'model_type' => 'required|string|in:audience_analysis,content_suggestions,engagement_prediction,best_time_prediction',
            'feedback_type' => 'required|string|in:positive,negative,neutral',
            'feedback_text' => 'required|string',
            'context_data' => 'nullable|array',
            'suggested_improvements' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $feedback = AIModelFeedback::create([
            'team_id' => Auth::user()->current_team_id,
            'model_type' => $request->model_type,
            'feedback_type' => $request->feedback_type,
            'feedback_text' => $request->feedback_text,
            'context_data' => $request->context_data,
            'suggested_improvements' => $request->suggested_improvements
        ]);

        return response()->json([
            'message' => 'تم إرسال الملاحظات بنجاح',
            'data' => $feedback
        ], 201);
    }

    public function show(AIModelFeedback $feedback)
    {
        $this->authorize('view', $feedback);

        return response()->json([
            'data' => $feedback
        ]);
    }

    public function resolve(Request $request, AIModelFeedback $feedback)
    {
        $this->authorize('update', $feedback);

        $validator = Validator::make($request->all(), [
            'resolution_notes' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $feedback->resolve($request->resolution_notes);

        return response()->json([
            'message' => 'تم حل الملاحظات بنجاح',
            'data' => $feedback
        ]);
    }

    public function getFeedbackStats()
    {
        $stats = AIModelFeedback::where('team_id', Auth::user()->current_team_id)
            ->selectRaw('
                model_type,
                feedback_type,
                COUNT(*) as count
            ')
            ->groupBy('model_type', 'feedback_type')
            ->get();

        return response()->json([
            'data' => $stats
        ]);
    }
} 