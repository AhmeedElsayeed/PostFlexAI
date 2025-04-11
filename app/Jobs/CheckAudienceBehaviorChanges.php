<?php

namespace App\Jobs;

use App\Models\AudienceInsight;
use App\Models\AudienceAlert;
use App\Services\AIAudienceAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckAudienceBehaviorChanges implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $accountId;

    public function __construct(int $accountId)
    {
        $this->accountId = $accountId;
    }

    public function handle(AIAudienceAnalysisService $aiService)
    {
        $currentInsight = AudienceInsight::where('social_account_id', $this->accountId)
            ->latest()
            ->first();

        if (!$currentInsight) {
            return;
        }

        $previousInsight = AudienceInsight::where('social_account_id', $this->accountId)
            ->where('id', '!=', $currentInsight->id)
            ->latest()
            ->first();

        if (!$previousInsight) {
            return;
        }

        $changes = $aiService->detectBehavioralChanges($currentInsight, $previousInsight);

        if (!$changes) {
            return;
        }

        foreach ($changes['significant_changes'] as $change) {
            $this->createAlert($currentInsight, $change);
        }
    }

    protected function createAlert(AudienceInsight $insight, array $change)
    {
        $severity = $this->determineSeverity($change);
        
        AudienceAlert::create([
            'team_id' => $insight->socialAccount->team_id,
            'social_account_id' => $insight->social_account_id,
            'type' => $change['type'] ?? 'behavior_change',
            'severity' => $severity,
            'message' => $change['description'] ?? 'Significant change in audience behavior detected',
            'details' => [
                'change' => $change,
                'current_metrics' => $insight->engagement_metrics,
                'previous_metrics' => $insight->engagement_metrics
            ]
        ]);
    }

    protected function determineSeverity(array $change): string
    {
        $impact = $change['impact'] ?? 0;
        
        if ($impact >= 50) {
            return 'high';
        } elseif ($impact >= 25) {
            return 'medium';
        }
        
        return 'low';
    }

    public function failed(\Throwable $exception)
    {
        \Log::error('Failed to check audience behavior changes for account ' . $this->accountId, [
            'error' => $exception->getMessage()
        ]);
    }
} 