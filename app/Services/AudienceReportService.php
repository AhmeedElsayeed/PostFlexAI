<?php

namespace App\Services;

use App\Models\AudienceInsight;
use App\Models\AudienceCluster;
use App\Models\SocialAccount;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use PDF;

class AudienceReportService
{
    protected $aiService;

    public function __construct(AIAudienceAnalysisService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function generateReport(Team $team, ?SocialAccount $account = null)
    {
        $data = $this->gatherReportData($team, $account);
        $aiInsights = $this->getAIInsights($data);
        
        $pdf = PDF::loadView('reports.audience', [
            'data' => $data,
            'aiInsights' => $aiInsights,
            'team' => $team,
            'account' => $account,
            'generatedAt' => now()
        ]);

        $filename = $this->generateFilename($team, $account);
        $path = 'reports/' . $filename;
        
        Storage::put($path, $pdf->output());
        
        return [
            'path' => $path,
            'filename' => $filename
        ];
    }

    protected function gatherReportData(Team $team, ?SocialAccount $account)
    {
        $data = [
            'overview' => [
                'total_followers' => 0,
                'total_engagement' => 0,
                'average_engagement_rate' => 0
            ],
            'demographics' => [],
            'interests' => [],
            'active_hours' => [],
            'content_preferences' => [],
            'engagement_metrics' => [],
            'growth_metrics' => [],
            'clusters' => []
        ];

        if ($account) {
            $insight = $account->latestInsight;
            if ($insight) {
                $data = $this->mergeInsightData($data, $insight);
            }
        } else {
            foreach ($team->socialAccounts as $socialAccount) {
                $insight = $socialAccount->latestInsight;
                if ($insight) {
                    $data = $this->mergeInsightData($data, $insight);
                }
            }
        }

        $data['clusters'] = $team->audienceClusters()->latest()->get();

        return $data;
    }

    protected function mergeInsightData(array $data, AudienceInsight $insight)
    {
        // Merge demographics
        foreach ($insight->demographics as $category => $values) {
            if (!isset($data['demographics'][$category])) {
                $data['demographics'][$category] = [];
            }
            foreach ($values as $key => $value) {
                if (!isset($data['demographics'][$category][$key])) {
                    $data['demographics'][$category][$key] = 0;
                }
                $data['demographics'][$category][$key] += $value;
            }
        }

        // Merge interests
        foreach ($insight->interests as $interest => $value) {
            if (!isset($data['interests'][$interest])) {
                $data['interests'][$interest] = 0;
            }
            $data['interests'][$interest] += $value;
        }

        // Merge active hours
        foreach ($insight->top_active_hours as $hour => $value) {
            if (!isset($data['active_hours'][$hour])) {
                $data['active_hours'][$hour] = 0;
            }
            $data['active_hours'][$hour] += $value;
        }

        // Merge content preferences
        foreach ($insight->content_preferences as $type => $value) {
            if (!isset($data['content_preferences'][$type])) {
                $data['content_preferences'][$type] = 0;
            }
            $data['content_preferences'][$type] += $value;
        }

        // Merge engagement metrics
        foreach ($insight->engagement_metrics as $metric => $value) {
            if (!isset($data['engagement_metrics'][$metric])) {
                $data['engagement_metrics'][$metric] = 0;
            }
            $data['engagement_metrics'][$metric] += $value;
        }

        // Merge growth metrics
        foreach ($insight->growth_metrics as $metric => $value) {
            if (!isset($data['growth_metrics'][$metric])) {
                $data['growth_metrics'][$metric] = 0;
            }
            if (is_array($value)) {
                if (!isset($data['growth_metrics'][$metric])) {
                    $data['growth_metrics'][$metric] = [];
                }
                foreach ($value as $key => $val) {
                    if (!isset($data['growth_metrics'][$metric][$key])) {
                        $data['growth_metrics'][$metric][$key] = 0;
                    }
                    $data['growth_metrics'][$metric][$key] += $val;
                }
            } else {
                $data['growth_metrics'][$metric] += $value;
            }
        }

        return $data;
    }

    protected function getAIInsights(array $data)
    {
        $insights = [];

        // Get AI analysis for each cluster
        foreach ($data['clusters'] as $cluster) {
            $insights[$cluster->id] = $this->aiService->generateContentRecommendations($cluster);
        }

        return $insights;
    }

    protected function generateFilename(Team $team, ?SocialAccount $account): string
    {
        $prefix = $account ? "audience-report-{$account->platform}" : 'audience-report-team';
        return $prefix . '-' . $team->id . '-' . now()->format('Y-m-d-H-i-s') . '.pdf';
    }
} 