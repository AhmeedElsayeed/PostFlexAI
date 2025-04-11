<?php

namespace App\Http\Controllers;

use App\Models\AdInsightAlert;
use App\Services\AdInsightAlertService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdInsightAlertController extends Controller
{
    protected $alertService;

    public function __construct(AdInsightAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function index(Request $request): JsonResponse
    {
        $alerts = AdInsightAlert::with(['ad', 'adAccount'])
            ->when($request->ad_id, function ($query, $adId) {
                return $query->where('ad_id', $adId);
            })
            ->when($request->ad_account_id, function ($query, $accountId) {
                return $query->where('ad_account_id', $accountId);
            })
            ->when($request->severity, function ($query, $severity) {
                return $query->where('severity', $severity);
            })
            ->when($request->type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($request->is_resolved !== null, function ($query) use ($request) {
                return $query->where('is_resolved', $request->is_resolved);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return response()->json($alerts);
    }

    public function show(AdInsightAlert $alert): JsonResponse
    {
        return response()->json($alert->load(['ad', 'adAccount']));
    }

    public function resolve(AdInsightAlert $alert): JsonResponse
    {
        $this->alertService->resolveAlert($alert);

        return response()->json([
            'message' => 'Alert resolved successfully',
            'alert' => $alert->fresh()
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total' => AdInsightAlert::count(),
            'unresolved' => AdInsightAlert::where('is_resolved', false)->count(),
            'by_severity' => [
                'high' => AdInsightAlert::where('severity', 'high')->count(),
                'medium' => AdInsightAlert::where('severity', 'medium')->count(),
                'low' => AdInsightAlert::where('severity', 'low')->count(),
            ],
            'by_type' => [
                'performance_drop' => AdInsightAlert::where('type', 'performance_drop')->count(),
                'cost_increase' => AdInsightAlert::where('type', 'cost_increase')->count(),
                'engagement_drop' => AdInsightAlert::where('type', 'engagement_drop')->count(),
            ]
        ];

        return response()->json($stats);
    }
} 