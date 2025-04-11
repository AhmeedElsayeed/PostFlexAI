<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdInsightRecommendation;
use App\Services\AdInsightRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdInsightRecommendationController extends Controller
{
    protected $recommendationService;

    public function __construct(AdInsightRecommendationService $recommendationService)
    {
        $this->recommendationService = $recommendationService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = AdInsightRecommendation::query()
            ->with(['ad', 'adAccount'])
            ->when($request->ad_id, function ($q) use ($request) {
                return $q->where('ad_id', $request->ad_id);
            })
            ->when($request->ad_account_id, function ($q) use ($request) {
                return $q->where('ad_account_id', $request->ad_account_id);
            })
            ->when($request->type, function ($q) use ($request) {
                return $q->where('type', $request->type);
            })
            ->when($request->priority, function ($q) use ($request) {
                return $q->where('priority', $request->priority);
            })
            ->when($request->is_implemented !== null, function ($q) use ($request) {
                return $q->where('is_implemented', $request->is_implemented);
            });

        $recommendations = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $recommendations,
            'message' => 'Recommendations retrieved successfully'
        ]);
    }

    public function show(AdInsightRecommendation $recommendation): JsonResponse
    {
        $recommendation->load(['ad', 'adAccount']);

        return response()->json([
            'data' => $recommendation,
            'message' => 'Recommendation retrieved successfully'
        ]);
    }

    public function generateForAd(Ad $ad): JsonResponse
    {
        $insights = $ad->insights()->latest()->first();
        
        if (!$insights) {
            return response()->json([
                'message' => 'No insights available for this ad'
            ], 404);
        }

        $recommendations = $this->recommendationService->generateRecommendations($ad, $insights->toArray());

        return response()->json([
            'data' => $recommendations,
            'message' => 'Recommendations generated successfully'
        ]);
    }

    public function implement(AdInsightRecommendation $recommendation): JsonResponse
    {
        $this->recommendationService->implementRecommendation($recommendation);

        return response()->json([
            'data' => $recommendation->fresh(),
            'message' => 'Recommendation implemented successfully'
        ]);
    }

    public function updateResults(Request $request, AdInsightRecommendation $recommendation): JsonResponse
    {
        $request->validate([
            'results' => 'required|array',
            'results.metrics_improvement' => 'required|array',
            'results.notes' => 'nullable|string'
        ]);

        $this->recommendationService->updateRecommendationResults($recommendation, $request->results);

        return response()->json([
            'data' => $recommendation->fresh(),
            'message' => 'Recommendation results updated successfully'
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $stats = AdInsightRecommendation::query()
            ->when($request->ad_account_id, function ($q) use ($request) {
                return $q->where('ad_account_id', $request->ad_account_id);
            })
            ->selectRaw('
                COUNT(*) as total_recommendations,
                SUM(CASE WHEN is_implemented = 1 THEN 1 ELSE 0 END) as implemented_recommendations,
                COUNT(DISTINCT type) as recommendation_types,
                COUNT(DISTINCT priority) as priority_levels
            ')
            ->first();

        $typeDistribution = AdInsightRecommendation::query()
            ->when($request->ad_account_id, function ($q) use ($request) {
                return $q->where('ad_account_id', $request->ad_account_id);
            })
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        $priorityDistribution = AdInsightRecommendation::query()
            ->when($request->ad_account_id, function ($q) use ($request) {
                return $q->where('ad_account_id', $request->ad_account_id);
            })
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get();

        return response()->json([
            'data' => [
                'overall_stats' => $stats,
                'type_distribution' => $typeDistribution,
                'priority_distribution' => $priorityDistribution
            ],
            'message' => 'Recommendation statistics retrieved successfully'
        ]);
    }
} 