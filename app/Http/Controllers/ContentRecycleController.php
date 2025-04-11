<?php

namespace App\Http\Controllers;

use App\Models\ContentRecycle;
use App\Models\Post;
use App\Services\ContentRecycleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContentRecycleController extends Controller
{
    protected $contentRecycleService;

    /**
     * Constructor.
     *
     * @param ContentRecycleService $contentRecycleService
     */
    public function __construct(ContentRecycleService $contentRecycleService)
    {
        $this->contentRecycleService = $contentRecycleService;
        $this->middleware('auth');
    }

    /**
     * Display a listing of content recycles.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = ContentRecycle::query()
            ->whereHas('post', function ($query) {
                $query->where('team_id', Auth::user()->current_team_id);
            })
            ->with(['post', 'creator']);

        // Apply filters
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        if ($request->has('strategy')) {
            $query->where('strategy', $request->strategy);
        }
        if ($request->has('is_approved')) {
            $query->where('is_approved', $request->boolean('is_approved'));
        }
        if ($request->has('min_score')) {
            $query->where('ai_score', '>=', $request->min_score);
        }

        // Apply search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('post', function ($q) use ($search) {
                $q->where('caption', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $contentRecycles = $query->paginate($perPage);

        return response()->json($contentRecycles);
    }

    /**
     * Store a newly created content recycle.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'post_id' => 'required|exists:posts,id',
            'strategy' => 'required|in:performance_improvement,time_change,similar_content_reuse',
            'new_caption' => 'nullable|string|max:2200',
            'new_schedule' => 'nullable|date'
        ]);

        $post = Post::findOrFail($request->post_id);
        
        // Check if user has permission to recycle this post
        if ($post->team_id !== Auth::user()->current_team_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $contentRecycle = $this->contentRecycleService->generateRecycledContent(
            $post,
            $request->strategy,
            Auth::user()
        );

        return response()->json($contentRecycle, 201);
    }

    /**
     * Display the specified content recycle.
     *
     * @param ContentRecycle $contentRecycle
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(ContentRecycle $contentRecycle)
    {
        // Check if user has permission to view this content recycle
        if ($contentRecycle->post->team_id !== Auth::user()->current_team_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $contentRecycle->load(['post', 'creator', 'recycledMedia']);

        return response()->json($contentRecycle);
    }

    /**
     * Update the specified content recycle.
     *
     * @param Request $request
     * @param ContentRecycle $contentRecycle
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, ContentRecycle $contentRecycle)
    {
        // Check if user has permission to update this content recycle
        if ($contentRecycle->post->team_id !== Auth::user()->current_team_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'new_caption' => 'nullable|string|max:2200',
            'new_schedule' => 'nullable|date',
            'is_approved' => 'nullable|boolean'
        ]);

        if ($request->has('new_caption')) {
            $contentRecycle->new_caption = $request->new_caption;
        }

        if ($request->has('new_schedule')) {
            $contentRecycle->new_schedule = Carbon::parse($request->new_schedule);
        }

        if ($request->has('is_approved')) {
            if ($request->is_approved) {
                $this->contentRecycleService->approveContentRecycle($contentRecycle);
            } else {
                $contentRecycle->is_approved = false;
            }
        }

        $contentRecycle->save();

        return response()->json($contentRecycle);
    }

    /**
     * Remove the specified content recycle.
     *
     * @param ContentRecycle $contentRecycle
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(ContentRecycle $contentRecycle)
    {
        // Check if user has permission to delete this content recycle
        if ($contentRecycle->post->team_id !== Auth::user()->current_team_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $contentRecycle->delete();

        return response()->json(null, 204);
    }

    /**
     * Get content recycling suggestions.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function suggestions(Request $request)
    {
        $limit = $request->get('limit', 10);
        
        $suggestions = $this->contentRecycleService->suggestPostsForRecycling(
            Auth::user()->current_team_id,
            $limit
        );

        return response()->json($suggestions);
    }

    /**
     * Schedule a recycled content.
     *
     * @param Request $request
     * @param ContentRecycle $contentRecycle
     * @return \Illuminate\Http\JsonResponse
     */
    public function schedule(Request $request, ContentRecycle $contentRecycle)
    {
        // Check if user has permission to schedule this content recycle
        if ($contentRecycle->post->team_id !== Auth::user()->current_team_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'schedule_time' => 'required|date'
        ]);

        $scheduleTime = Carbon::parse($request->schedule_time);
        
        $success = $this->contentRecycleService->scheduleRecycledContent(
            $contentRecycle,
            $scheduleTime
        );

        if (!$success) {
            return response()->json([
                'message' => 'Content recycle must be approved before scheduling'
            ], 422);
        }

        return response()->json($contentRecycle);
    }

    /**
     * Get recycling statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $stats = $this->contentRecycleService->getRecyclingStats(
            Auth::user()->current_team_id
        );

        return response()->json($stats);
    }

    /**
     * Compare performance of original and recycled content.
     *
     * @param ContentRecycle $contentRecycle
     * @return \Illuminate\Http\JsonResponse
     */
    public function comparePerformance(ContentRecycle $contentRecycle)
    {
        // Check if user has permission to view this content recycle
        if ($contentRecycle->post->team_id !== Auth::user()->current_team_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $comparison = $this->contentRecycleService->comparePerformance($contentRecycle);

        return response()->json($comparison);
    }
} 