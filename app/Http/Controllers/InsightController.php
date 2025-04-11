<?php

namespace App\Http\Controllers;

use App\Models\PostInsight;
use App\Models\AccountInsight;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class InsightController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:view_insights');
    }

    public function postInsights(Request $request)
    {
        $team = Team::findOrFail($request->team_id);
        $this->authorize('view', $team);

        $query = PostInsight::whereHas('post', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        });

        // Apply filters
        if ($request->has('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->has('start_date')) {
            $query->where('fetched_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('fetched_at', '<=', $request->end_date);
        }

        $insights = $query->with('post')->latest()->paginate(20);

        return response()->json($insights);
    }

    public function accountInsights(Request $request)
    {
        $team = Team::findOrFail($request->team_id);
        $this->authorize('view', $team);

        $query = AccountInsight::whereHas('socialAccount', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        });

        // Apply filters
        if ($request->has('platform')) {
            $query->whereHas('socialAccount', function ($query) use ($request) {
                $query->where('platform', $request->platform);
            });
        }

        if ($request->has('start_date')) {
            $query->where('fetched_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('fetched_at', '<=', $request->end_date);
        }

        $insights = $query->with('socialAccount')->latest()->paginate(20);

        return response()->json($insights);
    }

    public function dashboardOverview(Request $request)
    {
        $team = Team::findOrFail($request->team_id);
        $this->authorize('view', $team);

        $overview = [
            'top_posts' => $this->getTopPosts($team),
            'best_performing_platform' => $this->getBestPerformingPlatform($team),
            'engagement_rate' => $this->getAverageEngagementRate($team),
            'content_type_analysis' => $this->getContentTypeAnalysis($team),
            'best_posting_times' => $this->getBestPostingTimes($team)
        ];

        return response()->json($overview);
    }

    public function exportReport(Request $request)
    {
        $team = Team::findOrFail($request->team_id);
        $this->authorize('view', $team);

        $data = [
            'team' => $team,
            'post_insights' => $this->getPostInsightsForExport($team, $request),
            'account_insights' => $this->getAccountInsightsForExport($team, $request),
            'period' => [
                'start' => $request->start_date,
                'end' => $request->end_date
            ]
        ];

        $pdf = Pdf::loadView('reports.insights', $data);
        
        return $pdf->download('insights-report.pdf');
    }

    protected function getTopPosts(Team $team)
    {
        return PostInsight::whereHas('post', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })
        ->orderBy('engagement_rate', 'desc')
        ->take(5)
        ->with('post')
        ->get();
    }

    protected function getBestPerformingPlatform(Team $team)
    {
        return PostInsight::whereHas('post', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })
        ->selectRaw('platform, AVG(engagement_rate) as avg_engagement')
        ->groupBy('platform')
        ->orderBy('avg_engagement', 'desc')
        ->first();
    }

    protected function getAverageEngagementRate(Team $team)
    {
        return PostInsight::whereHas('post', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })
        ->avg('engagement_rate');
    }

    protected function getContentTypeAnalysis(Team $team)
    {
        return PostInsight::whereHas('post', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })
        ->selectRaw('post_type, AVG(engagement_rate) as avg_engagement')
        ->groupBy('post_type')
        ->orderBy('avg_engagement', 'desc')
        ->get();
    }

    protected function getBestPostingTimes(Team $team)
    {
        return PostInsight::whereHas('post', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        })
        ->selectRaw('HOUR(scheduled_at) as hour, AVG(engagement_rate) as avg_engagement')
        ->groupBy('hour')
        ->orderBy('avg_engagement', 'desc')
        ->take(3)
        ->get();
    }

    protected function getPostInsightsForExport(Team $team, Request $request)
    {
        $query = PostInsight::whereHas('post', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        });

        if ($request->has('start_date')) {
            $query->where('fetched_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('fetched_at', '<=', $request->end_date);
        }

        return $query->with('post')->get();
    }

    protected function getAccountInsightsForExport(Team $team, Request $request)
    {
        $query = AccountInsight::whereHas('socialAccount', function ($query) use ($team) {
            $query->where('team_id', $team->id);
        });

        if ($request->has('start_date')) {
            $query->where('fetched_at', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('fetched_at', '<=', $request->end_date);
        }

        return $query->with('socialAccount')->get();
    }
} 