<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdInsight;
use App\Models\AdInsightAlert;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdInsightAlertService
{
    protected $thresholds = [
        'performance_drop' => [
            'ctr' => 0.2, // 20% drop in CTR
            'conversion_rate' => 0.25, // 25% drop in conversion rate
            'cost_per_conversion' => 0.3, // 30% increase in cost per conversion
        ],
        'cost_increase' => [
            'cpc' => 0.25, // 25% increase in CPC
            'spend' => 0.3, // 30% increase in spend
        ],
        'engagement_drop' => [
            'impressions' => 0.2, // 20% drop in impressions
            'clicks' => 0.25, // 25% drop in clicks
        ]
    ];

    public function checkForAlerts(Ad $ad, array $currentInsights, array $previousInsights): array
    {
        $alerts = [];

        // Check for performance drops
        if ($this->hasPerformanceDrop($currentInsights, $previousInsights)) {
            $alerts[] = $this->createAlert($ad, 'performance_drop', $currentInsights, $previousInsights);
        }

        // Check for cost increases
        if ($this->hasCostIncrease($currentInsights, $previousInsights)) {
            $alerts[] = $this->createAlert($ad, 'cost_increase', $currentInsights, $previousInsights);
        }

        // Check for engagement drops
        if ($this->hasEngagementDrop($currentInsights, $previousInsights)) {
            $alerts[] = $this->createAlert($ad, 'engagement_drop', $currentInsights, $previousInsights);
        }

        return $alerts;
    }

    protected function hasPerformanceDrop(array $current, array $previous): bool
    {
        if (empty($previous)) return false;

        $ctrDrop = ($previous['ctr'] - $current['ctr']) / $previous['ctr'];
        $conversionDrop = ($previous['conversion_rate'] - $current['conversion_rate']) / $previous['conversion_rate'];
        $costIncrease = ($current['cost_per_conversion'] - $previous['cost_per_conversion']) / $previous['cost_per_conversion'];

        return $ctrDrop >= $this->thresholds['performance_drop']['ctr'] ||
               $conversionDrop >= $this->thresholds['performance_drop']['conversion_rate'] ||
               $costIncrease >= $this->thresholds['performance_drop']['cost_per_conversion'];
    }

    protected function hasCostIncrease(array $current, array $previous): bool
    {
        if (empty($previous)) return false;

        $cpcIncrease = ($current['cpc'] - $previous['cpc']) / $previous['cpc'];
        $spendIncrease = ($current['spend'] - $previous['spend']) / $previous['spend'];

        return $cpcIncrease >= $this->thresholds['cost_increase']['cpc'] ||
               $spendIncrease >= $this->thresholds['cost_increase']['spend'];
    }

    protected function hasEngagementDrop(array $current, array $previous): bool
    {
        if (empty($previous)) return false;

        $impressionsDrop = ($previous['impressions'] - $current['impressions']) / $previous['impressions'];
        $clicksDrop = ($previous['clicks'] - $current['clicks']) / $previous['clicks'];

        return $impressionsDrop >= $this->thresholds['engagement_drop']['impressions'] ||
               $clicksDrop >= $this->thresholds['engagement_drop']['clicks'];
    }

    protected function createAlert(Ad $ad, string $type, array $current, array $previous): AdInsightAlert
    {
        $severity = $this->calculateSeverity($type, $current, $previous);
        $message = $this->generateAlertMessage($type, $severity, $current, $previous);

        return AdInsightAlert::create([
            'ad_id' => $ad->id,
            'ad_account_id' => $ad->ad_account_id,
            'type' => $type,
            'severity' => $severity,
            'message' => $message,
            'metrics' => $current,
            'comparison_data' => $previous
        ]);
    }

    protected function calculateSeverity(string $type, array $current, array $previous): string
    {
        if (empty($previous)) return 'low';

        $changes = [];
        switch ($type) {
            case 'performance_drop':
                $changes[] = ($previous['ctr'] - $current['ctr']) / $previous['ctr'];
                $changes[] = ($previous['conversion_rate'] - $current['conversion_rate']) / $previous['conversion_rate'];
                $changes[] = ($current['cost_per_conversion'] - $previous['cost_per_conversion']) / $previous['cost_per_conversion'];
                break;
            case 'cost_increase':
                $changes[] = ($current['cpc'] - $previous['cpc']) / $previous['cpc'];
                $changes[] = ($current['spend'] - $previous['spend']) / $previous['spend'];
                break;
            case 'engagement_drop':
                $changes[] = ($previous['impressions'] - $current['impressions']) / $previous['impressions'];
                $changes[] = ($previous['clicks'] - $current['clicks']) / $previous['clicks'];
                break;
        }

        $maxChange = max($changes);
        if ($maxChange >= 0.5) return 'high';
        if ($maxChange >= 0.3) return 'medium';
        return 'low';
    }

    protected function generateAlertMessage(string $type, string $severity, array $current, array $previous): string
    {
        $messages = [
            'performance_drop' => [
                'high' => 'Significant drop in ad performance detected',
                'medium' => 'Moderate drop in ad performance detected',
                'low' => 'Slight drop in ad performance detected'
            ],
            'cost_increase' => [
                'high' => 'Significant increase in ad costs detected',
                'medium' => 'Moderate increase in ad costs detected',
                'low' => 'Slight increase in ad costs detected'
            ],
            'engagement_drop' => [
                'high' => 'Significant drop in ad engagement detected',
                'medium' => 'Moderate drop in ad engagement detected',
                'low' => 'Slight drop in ad engagement detected'
            ]
        ];

        return $messages[$type][$severity];
    }

    public function resolveAlert(AdInsightAlert $alert): void
    {
        $alert->update([
            'is_resolved' => true,
            'resolved_at' => now()
        ]);
    }
} 