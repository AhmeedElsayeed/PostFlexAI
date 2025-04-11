<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdInsight;
use App\Models\AdInsightRecommendation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdInsightRecommendationService
{
    protected $thresholds = [
        'budget_optimization' => [
            'cpc' => 0.3, // 30% higher than average
            'cost_per_conversion' => 0.4, // 40% higher than average
            'spend' => 0.5, // 50% higher than average
        ],
        'targeting_improvement' => [
            'ctr' => 0.2, // 20% lower than average
            'conversion_rate' => 0.25, // 25% lower than average
            'impressions' => 0.3, // 30% lower than average
        ],
        'creative_enhancement' => [
            'ctr' => 0.15, // 15% lower than average
            'clicks' => 0.2, // 20% lower than average
            'engagement_rate' => 0.25, // 25% lower than average
        ]
    ];

    public function generateRecommendations(Ad $ad, array $insights): array
    {
        $recommendations = [];

        // Check for budget optimization opportunities
        if ($this->needsBudgetOptimization($insights)) {
            $recommendations[] = $this->createBudgetOptimizationRecommendation($ad, $insights);
        }

        // Check for targeting improvements
        if ($this->needsTargetingImprovement($insights)) {
            $recommendations[] = $this->createTargetingImprovementRecommendation($ad, $insights);
        }

        // Check for creative enhancements
        if ($this->needsCreativeEnhancement($insights)) {
            $recommendations[] = $this->createCreativeEnhancementRecommendation($ad, $insights);
        }

        return $recommendations;
    }

    protected function needsBudgetOptimization(array $insights): bool
    {
        $avgCpc = $this->calculateAverageMetric($insights, 'cpc');
        $avgCostPerConversion = $this->calculateAverageMetric($insights, 'cost_per_conversion');
        $avgSpend = $this->calculateAverageMetric($insights, 'spend');

        $currentCpc = $insights['cpc'];
        $currentCostPerConversion = $insights['cost_per_conversion'];
        $currentSpend = $insights['spend'];

        return ($currentCpc - $avgCpc) / $avgCpc >= $this->thresholds['budget_optimization']['cpc'] ||
               ($currentCostPerConversion - $avgCostPerConversion) / $avgCostPerConversion >= $this->thresholds['budget_optimization']['cost_per_conversion'] ||
               ($currentSpend - $avgSpend) / $avgSpend >= $this->thresholds['budget_optimization']['spend'];
    }

    protected function needsTargetingImprovement(array $insights): bool
    {
        $avgCtr = $this->calculateAverageMetric($insights, 'ctr');
        $avgConversionRate = $this->calculateAverageMetric($insights, 'conversion_rate');
        $avgImpressions = $this->calculateAverageMetric($insights, 'impressions');

        $currentCtr = $insights['ctr'];
        $currentConversionRate = $insights['conversion_rate'];
        $currentImpressions = $insights['impressions'];

        return ($avgCtr - $currentCtr) / $avgCtr >= $this->thresholds['targeting_improvement']['ctr'] ||
               ($avgConversionRate - $currentConversionRate) / $avgConversionRate >= $this->thresholds['targeting_improvement']['conversion_rate'] ||
               ($avgImpressions - $currentImpressions) / $avgImpressions >= $this->thresholds['targeting_improvement']['impressions'];
    }

    protected function needsCreativeEnhancement(array $insights): bool
    {
        $avgCtr = $this->calculateAverageMetric($insights, 'ctr');
        $avgClicks = $this->calculateAverageMetric($insights, 'clicks');
        $avgEngagementRate = $this->calculateAverageMetric($insights, 'engagement_rate');

        $currentCtr = $insights['ctr'];
        $currentClicks = $insights['clicks'];
        $currentEngagementRate = $insights['engagement_rate'] ?? 0;

        return ($avgCtr - $currentCtr) / $avgCtr >= $this->thresholds['creative_enhancement']['ctr'] ||
               ($avgClicks - $currentClicks) / $avgClicks >= $this->thresholds['creative_enhancement']['clicks'] ||
               ($avgEngagementRate - $currentEngagementRate) / $avgEngagementRate >= $this->thresholds['creative_enhancement']['engagement_rate'];
    }

    protected function calculateAverageMetric(array $insights, string $metric): float
    {
        // In a real implementation, this would calculate the average from historical data
        // For now, we'll use some reasonable default values
        $defaults = [
            'cpc' => 1.5,
            'cost_per_conversion' => 25.0,
            'spend' => 100.0,
            'ctr' => 0.02,
            'conversion_rate' => 0.05,
            'impressions' => 5000,
            'clicks' => 100,
            'engagement_rate' => 0.03
        ];

        return $defaults[$metric] ?? 0;
    }

    protected function createBudgetOptimizationRecommendation(Ad $ad, array $insights): AdInsightRecommendation
    {
        return AdInsightRecommendation::create([
            'ad_id' => $ad->id,
            'ad_account_id' => $ad->ad_account_id,
            'type' => 'budget_optimization',
            'priority' => 'high',
            'title' => 'Optimize Ad Budget Allocation',
            'description' => 'Your ad is spending more than average on clicks and conversions. Consider adjusting your budget allocation to improve ROI.',
            'metrics_impact' => [
                'expected_cpc_reduction' => '15-20%',
                'expected_conversion_cost_reduction' => '20-25%',
                'expected_roi_improvement' => '25-30%'
            ],
            'implementation_steps' => [
                'Review current budget allocation',
                'Identify high-performing segments',
                'Adjust bids for underperforming segments',
                'Implement budget caps for expensive placements',
                'Monitor performance after changes'
            ]
        ]);
    }

    protected function createTargetingImprovementRecommendation(Ad $ad, array $insights): AdInsightRecommendation
    {
        return AdInsightRecommendation::create([
            'ad_id' => $ad->id,
            'ad_account_id' => $ad->ad_account_id,
            'type' => 'targeting_improvement',
            'priority' => 'medium',
            'title' => 'Improve Ad Targeting',
            'description' => 'Your ad is underperforming in terms of CTR and conversions. Consider refining your targeting parameters.',
            'metrics_impact' => [
                'expected_ctr_improvement' => '20-25%',
                'expected_conversion_rate_improvement' => '15-20%',
                'expected_reach_improvement' => '30-35%'
            ],
            'implementation_steps' => [
                'Analyze current audience demographics',
                'Review interest targeting',
                'Adjust location targeting if needed',
                'Refine age and gender targeting',
                'Test new audience segments'
            ]
        ]);
    }

    protected function createCreativeEnhancementRecommendation(Ad $ad, array $insights): AdInsightRecommendation
    {
        return AdInsightRecommendation::create([
            'ad_id' => $ad->id,
            'ad_account_id' => $ad->ad_account_id,
            'type' => 'creative_enhancement',
            'priority' => 'medium',
            'title' => 'Enhance Ad Creative',
            'description' => 'Your ad creative could be more engaging. Consider updating the visuals and copy to improve performance.',
            'metrics_impact' => [
                'expected_ctr_improvement' => '25-30%',
                'expected_engagement_improvement' => '20-25%',
                'expected_brand_recognition_improvement' => '15-20%'
            ],
            'implementation_steps' => [
                'Review current creative performance',
                'Update visual elements',
                'Refine ad copy',
                'Test different creative formats',
                'Implement A/B testing'
            ]
        ]);
    }

    public function implementRecommendation(AdInsightRecommendation $recommendation): void
    {
        $recommendation->update([
            'is_implemented' => true,
            'implemented_at' => now()
        ]);
    }

    public function updateRecommendationResults(AdInsightRecommendation $recommendation, array $results): void
    {
        $recommendation->update([
            'results' => $results
        ]);
    }
} 