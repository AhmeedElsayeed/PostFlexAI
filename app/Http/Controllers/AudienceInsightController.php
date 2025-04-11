<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\Team;
use App\Models\AudienceAlert;
use App\Services\AudienceAnalysisService;
use App\Services\AIAudienceAnalysisService;
use App\Services\AudienceReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\AudienceComparison;
use Carbon\Carbon;

class AudienceInsightController extends Controller
{
    protected $audienceAnalysisService;
    protected $aiService;
    protected $reportService;

    public function __construct(
        AudienceAnalysisService $audienceAnalysisService,
        AIAudienceAnalysisService $aiService,
        AudienceReportService $reportService
    ) {
        $this->audienceAnalysisService = $audienceAnalysisService;
        $this->aiService = $aiService;
        $this->reportService = $reportService;
    }

    public function getInsights(SocialAccount $account)
    {
        $this->authorize('view', $account);

        $insights = $this->audienceAnalysisService->analyzeAccount($account);
        $aiAnalysis = $this->aiService->analyzeAudienceBehavior($insights);

        return response()->json([
            'insights' => $insights,
            'ai_analysis' => $aiAnalysis,
            'engagement_rate' => $insights->engagement_rate,
            'top_interest' => $insights->top_interest,
            'best_posting_time' => $insights->best_posting_time
        ]);
    }

    public function comparePlatforms(Request $request)
    {
        $team = $request->user()->currentTeam;
        $this->authorize('view', $team);

        $comparison = $this->audienceAnalysisService->comparePlatforms($team);

        return response()->json([
            'comparison' => $comparison,
            'summary' => [
                'best_platform' => $this->getBestPlatform($comparison),
                'total_followers' => array_sum(array_column($comparison, 'total_followers')),
                'average_engagement' => $this->calculateAverageEngagement($comparison)
            ]
        ]);
    }

    public function recommendSegments(Request $request)
    {
        $team = $request->user()->currentTeam;
        $this->authorize('view', $team);

        $recommendations = $this->audienceAnalysisService->recommendSegments($team);

        return response()->json([
            'segments' => $recommendations,
            'summary' => [
                'total_segments' => count($recommendations),
                'total_audience' => array_sum(array_column($recommendations, 'size')),
                'average_engagement' => $this->calculateAverageSegmentEngagement($recommendations)
            ]
        ]);
    }

    public function getAlerts(Request $request)
    {
        $team = $request->user()->currentTeam;
        $this->authorize('view', $team);

        $alerts = AudienceAlert::where('team_id', $team->id)
            ->with('socialAccount')
            ->latest()
            ->paginate(10);

        return response()->json($alerts);
    }

    public function markAlertAsRead(AudienceAlert $alert)
    {
        $this->authorize('view', $alert->team);
        
        $alert->markAsRead();
        return response()->json(['message' => 'Alert marked as read']);
    }

    public function markAlertAsResolved(AudienceAlert $alert)
    {
        $this->authorize('view', $alert->team);
        
        $alert->markAsResolved();
        return response()->json(['message' => 'Alert marked as resolved']);
    }

    public function generateReport(Request $request, ?SocialAccount $account = null)
    {
        $team = $request->user()->currentTeam;
        $this->authorize('view', $team);

        if ($account && $account->team_id !== $team->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $report = $this->reportService->generateReport($team, $account);

        return response()->json([
            'message' => 'Report generated successfully',
            'download_url' => route('audience-insights.download-report', ['filename' => $report['filename']])
        ]);
    }

    public function downloadReport(string $filename)
    {
        $path = 'reports/' . $filename;
        
        if (!Storage::exists($path)) {
            return response()->json(['error' => 'Report not found'], 404);
        }

        return Storage::download($path);
    }

    public function generateComparison(Request $request, SocialAccount $account)
    {
        $validator = Validator::make($request->all(), [
            'metric_type' => 'required|string|in:engagement,growth,demographics,behavior',
            'period_type' => 'required|string|in:week,month,quarter,year',
            'end_date' => 'nullable|date'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $this->authorize('view', $account);

        $comparison = $this->audienceAnalysisService->generateComparison(
            $account,
            $request->metric_type,
            $request->period_type,
            $request->end_date ? Carbon::parse($request->end_date) : null
        );

        return response()->json([
            'message' => 'تم إنشاء المقارنة بنجاح',
            'data' => $comparison
        ]);
    }

    public function getComparisons(Request $request, SocialAccount $account)
    {
        $this->authorize('view', $account);

        $query = AudienceComparison::where('social_account_id', $account->id);

        if ($request->has('metric_type')) {
            $query->where('metric_type', $request->metric_type);
        }

        if ($request->has('period_type')) {
            $query->where('period_type', $request->period_type);
        }

        $comparisons = $query->latest()->paginate(10);

        return response()->json([
            'data' => $comparisons,
            'meta' => [
                'total' => $comparisons->total(),
                'per_page' => $comparisons->perPage(),
                'current_page' => $comparisons->currentPage(),
                'last_page' => $comparisons->lastPage()
            ]
        ]);
    }

    public function getComparison(AudienceComparison $comparison)
    {
        $this->authorize('view', $comparison->socialAccount);

        return response()->json([
            'data' => $comparison
        ]);
    }

    protected function getBestPlatform($comparison)
    {
        $best = null;
        $highestEngagement = 0;

        foreach ($comparison as $platform => $data) {
            if ($data['engagement_rate'] > $highestEngagement) {
                $highestEngagement = $data['engagement_rate'];
                $best = $platform;
            }
        }

        return $best;
    }

    protected function calculateAverageEngagement($comparison)
    {
        $total = 0;
        $count = 0;

        foreach ($comparison as $data) {
            $total += $data['engagement_rate'];
            $count++;
        }

        return $count > 0 ? $total / $count : 0;
    }

    protected function calculateAverageSegmentEngagement($recommendations)
    {
        $total = 0;
        $count = 0;

        foreach ($recommendations as $segment) {
            $total += $segment['engagement_rate'];
            $count++;
        }

        return $count > 0 ? $total / $count : 0;
    }
} 