<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\AdInsight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AdInsightService
{
    protected $alertService;

    public function __construct(AdInsightAlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    public function fetchAndUpdateInsights(int $adAccountId, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subDays(30);
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now();

        $ads = Ad::where('ad_account_id', $adAccountId)->get();
        $updatedInsights = [];

        foreach ($ads as $ad) {
            try {
                // Here you would typically make an API call to the ad platform (e.g., Facebook, Google Ads)
                // to fetch the actual insights data. For now, we'll simulate with dummy data
                $insights = $this->fetchInsightsFromPlatform($ad, $startDate, $endDate);
                
                foreach ($insights as $insight) {
                    $updatedInsight = AdInsight::updateOrCreate(
                        [
                            'ad_id' => $ad->id,
                            'ad_account_id' => $adAccountId,
                            'date' => $insight['date']
                        ],
                        [
                            'impressions' => $insight['impressions'],
                            'clicks' => $insight['clicks'],
                            'spend' => $insight['spend'],
                            'cpc' => $insight['cpc'],
                            'ctr' => $insight['ctr'],
                            'conversions' => $insight['conversions'],
                            'conversion_rate' => $insight['conversion_rate'],
                            'cost_per_conversion' => $insight['cost_per_conversion'],
                            'breakdown_data' => $insight['breakdown_data'] ?? null
                        ]
                    );

                    // Get previous day's insights for comparison
                    $previousInsight = AdInsight::where('ad_id', $ad->id)
                        ->where('date', '<', $insight['date'])
                        ->orderBy('date', 'desc')
                        ->first();

                    // Check for alerts if we have previous data
                    if ($previousInsight) {
                        $this->alertService->checkForAlerts(
                            $ad,
                            $updatedInsight->toArray(),
                            $previousInsight->toArray()
                        );
                    }

                    $updatedInsights[] = $updatedInsight;
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch insights for ad {$ad->id}: " . $e->getMessage());
                continue;
            }
        }

        return $updatedInsights;
    }

    protected function fetchInsightsFromPlatform(Ad $ad, Carbon $startDate, Carbon $endDate): array
    {
        // This is a placeholder method. In a real implementation, you would:
        // 1. Get the appropriate API client based on the ad platform
        // 2. Make the API call to fetch insights
        // 3. Transform the response into the expected format
        
        // For now, we'll return dummy data
        $insights = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $insights[] = [
                'date' => $currentDate->format('Y-m-d'),
                'impressions' => rand(100, 1000),
                'clicks' => rand(10, 100),
                'spend' => rand(50, 500) / 100,
                'cpc' => rand(50, 200) / 100,
                'ctr' => rand(100, 1000) / 100,
                'conversions' => rand(1, 20),
                'conversion_rate' => rand(100, 1000) / 100,
                'cost_per_conversion' => rand(100, 1000) / 100,
                'breakdown_data' => [
                    'device' => [
                        'mobile' => rand(40, 60),
                        'desktop' => rand(30, 50),
                        'tablet' => rand(10, 20)
                    ],
                    'gender' => [
                        'male' => rand(40, 60),
                        'female' => rand(40, 60)
                    ],
                    'age' => [
                        '18-24' => rand(20, 30),
                        '25-34' => rand(25, 35),
                        '35-44' => rand(20, 30),
                        '45-54' => rand(15, 25),
                        '55+' => rand(10, 20)
                    ]
                ]
            ];

            $currentDate->addDay();
        }

        return $insights;
    }
} 