<?php

namespace App\Http\Controllers;

use App\Models\AdInsight;
use App\Services\AdInsightService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdInsightController extends Controller
{
    protected $adInsightService;

    public function __construct(AdInsightService $adInsightService)
    {
        $this->adInsightService = $adInsightService;
    }

    public function index(Request $request): JsonResponse
    {
        $insights = AdInsight::with(['ad', 'adAccount'])
            ->when($request->ad_id, function ($query, $adId) {
                return $query->where('ad_id', $adId);
            })
            ->when($request->ad_account_id, function ($query, $accountId) {
                return $query->where('ad_account_id', $accountId);
            })
            ->when($request->start_date, function ($query, $date) {
                return $query->where('date', '>=', $date);
            })
            ->when($request->end_date, function ($query, $date) {
                return $query->where('date', '<=', $date);
            })
            ->orderBy('date', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($insights);
    }

    public function show(AdInsight $adInsight): JsonResponse
    {
        return response()->json($adInsight->load(['ad', 'adAccount']));
    }

    public function fetchInsights(Request $request): JsonResponse
    {
        try {
            $result = $this->adInsightService->fetchAndUpdateInsights(
                $request->ad_account_id,
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'message' => 'Ad insights fetched successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch ad insights',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 