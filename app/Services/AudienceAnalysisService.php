<?php

namespace App\Services;

use App\Models\AudienceInsight;
use App\Models\AudienceCluster;
use App\Models\SocialAccount;
use App\Models\Team;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use App\Models\AudienceComparison;
use App\Models\AudiencePersona;

class AudienceAnalysisService
{
    public function analyzeAccount(SocialAccount $account)
    {
        $platform = $account->platform;
        $insights = $this->fetchPlatformInsights($account);

        return AudienceInsight::updateOrCreate(
            ['social_account_id' => $account->id],
            [
                'platform' => $platform,
                'demographics' => $insights['demographics'],
                'interests' => $insights['interests'],
                'top_active_hours' => $insights['active_hours'],
                'content_preferences' => $insights['content_preferences'],
                'engagement_metrics' => $insights['engagement'],
                'growth_metrics' => $insights['growth']
            ]
        );
    }

    public function analyzeTeamAudience(Team $team)
    {
        $accounts = $team->socialAccounts;
        $allInsights = collect();

        foreach ($accounts as $account) {
            $insights = $this->analyzeAccount($account);
            $allInsights->push($insights);
        }

        return $this->generateAudienceClusters($team, $allInsights);
    }

    public function comparePlatforms(Team $team)
    {
        $accounts = $team->socialAccounts;
        $comparison = [];

        foreach ($accounts as $account) {
            $insights = $account->latestInsight;
            if (!$insights) continue;

            $comparison[$account->platform] = [
                'total_followers' => $insights->growth_metrics['followers'] ?? 0,
                'engagement_rate' => $insights->engagement_rate,
                'top_interests' => array_slice($insights->interests, 0, 5),
                'best_posting_time' => $insights->best_posting_time,
                'content_preferences' => $insights->content_preferences
            ];
        }

        return $comparison;
    }

    public function recommendSegments(Team $team)
    {
        $clusters = $team->audienceClusters;
        $recommendations = [];

        foreach ($clusters as $cluster) {
            $recommendations[] = [
                'name' => $cluster->name,
                'size' => $cluster->size,
                'engagement_rate' => $cluster->engagement_rate,
                'content_recommendations' => $cluster->content_recommendations,
                'best_posting_time' => $cluster->best_posting_time,
                'characteristics' => $cluster->characteristics
            ];
        }

        return $recommendations;
    }

    public function generateComparison(
        SocialAccount $account,
        string $metricType,
        string $periodType,
        ?Carbon $endDate = null
    ): AudienceComparison {
        $endDate = $endDate ?? now();
        $periods = $this->calculatePeriods($periodType, $endDate);

        $currentData = $this->getMetricData($account, $metricType, $periods['current']);
        $previousData = $this->getMetricData($account, $metricType, $periods['previous']);

        $changePercentage = $this->calculateChangePercentage($currentData, $previousData);
        $insights = $this->generateComparisonInsights($currentData, $previousData, $metricType);

        return AudienceComparison::create([
            'team_id' => $account->team_id,
            'social_account_id' => $account->id,
            'metric_type' => $metricType,
            'current_period_data' => $currentData,
            'previous_period_data' => $previousData,
            'change_percentage' => $changePercentage,
            'period_type' => $periodType,
            'current_period_start' => $periods['current']['start'],
            'current_period_end' => $periods['current']['end'],
            'previous_period_start' => $periods['previous']['start'],
            'previous_period_end' => $periods['previous']['end'],
            'insights' => $insights
        ]);
    }

    public function generatePersona(SocialAccount $account, array $clusterData): AudiencePersona
    {
        $insights = $this->fetchPlatformInsights($account);
        $demographics = $this->analyzeDemographics($insights);
        $behaviors = $this->analyzeBehaviors($insights);
        $contentPreferences = $this->analyzeContentPreferences($insights);
        $engagementPatterns = $this->analyzeEngagementPatterns($insights);

        // Generate persona name and description
        $name = $this->generatePersonaName($demographics, $behaviors);
        $description = $this->generatePersonaDescription($demographics, $behaviors, $contentPreferences);

        // Analyze interests and pain points
        $interests = $this->analyzeInterests($contentPreferences, $behaviors);
        $painPoints = $this->analyzePainPoints($behaviors, $contentPreferences);
        $goals = $this->analyzeGoals($behaviors, $contentPreferences);

        // Analyze brand interactions
        $brandInteractions = $this->analyzeBrandInteractions($insights);

        // Calculate engagement metrics
        $engagementRate = $this->calculateEngagementRate($insights);
        $estimatedSize = $this->estimatePersonaSize($clusterData);

        // Generate recommendations
        $recommendations = $this->generatePersonaRecommendations(
            $interests,
            $painPoints,
            $goals,
            $contentPreferences
        );

        return AudiencePersona::create([
            'team_id' => $account->team_id,
            'social_account_id' => $account->id,
            'name' => $name,
            'description' => $description,
            'demographics' => $demographics,
            'interests' => $interests,
            'behaviors' => $behaviors,
            'content_preferences' => $contentPreferences,
            'engagement_patterns' => $engagementPatterns,
            'pain_points' => $painPoints,
            'goals' => $goals,
            'brand_interactions' => $brandInteractions,
            'engagement_rate' => $engagementRate,
            'estimated_size' => $estimatedSize,
            'recommendations' => $recommendations
        ]);
    }

    protected function fetchPlatformInsights(SocialAccount $account)
    {
        // This is a placeholder. In a real implementation, you would:
        // 1. Call the platform's API (Facebook Graph API, Instagram API, etc.)
        // 2. Process and normalize the data
        // 3. Return structured insights

        return [
            'demographics' => [
                'age' => ['18-24' => 30, '25-34' => 40, '35-44' => 20, '45+' => 10],
                'gender' => ['male' => 45, 'female' => 55],
                'location' => ['city1' => 30, 'city2' => 25, 'city3' => 20, 'others' => 25]
            ],
            'interests' => [
                'technology' => 75,
                'business' => 60,
                'entertainment' => 45,
                'sports' => 30
            ],
            'active_hours' => [
                '09:00' => 80,
                '10:00' => 90,
                '11:00' => 85,
                '12:00' => 70
            ],
            'content_preferences' => [
                'video' => 60,
                'image' => 30,
                'text' => 10
            ],
            'engagement' => [
                'likes' => 1000,
                'comments' => 100,
                'shares' => 50
            ],
            'growth' => [
                'followers' => 5000,
                'reach' => [10000, 12000, 15000],
                'impressions' => [15000, 18000, 20000]
            ]
        ];
    }

    protected function generateAudienceClusters(Team $team, Collection $insights)
    {
        // This is a placeholder. In a real implementation, you would:
        // 1. Use machine learning algorithms to cluster the audience
        // 2. Generate characteristics for each cluster
        // 3. Create content recommendations based on cluster behavior

        $clusters = [
            [
                'name' => 'Tech Enthusiasts',
                'characteristics' => [
                    'size' => 2000,
                    'engagement_rate' => 4.5,
                    'age_range' => '25-34',
                    'interests' => ['technology', 'business']
                ],
                'content_recommendations' => [
                    'video' => 70,
                    'image' => 20,
                    'text' => 10
                ],
                'best_posting_times' => [
                    '09:00' => 90,
                    '10:00' => 85
                ]
            ],
            [
                'name' => 'Casual Followers',
                'characteristics' => [
                    'size' => 3000,
                    'engagement_rate' => 2.1,
                    'age_range' => '18-24',
                    'interests' => ['entertainment', 'sports']
                ],
                'content_recommendations' => [
                    'image' => 50,
                    'video' => 40,
                    'text' => 10
                ],
                'best_posting_times' => [
                    '11:00' => 80,
                    '12:00' => 75
                ]
            ]
        ];

        foreach ($clusters as $cluster) {
            AudienceCluster::updateOrCreate(
                [
                    'team_id' => $team->id,
                    'name' => $cluster['name']
                ],
                [
                    'characteristics' => $cluster['characteristics'],
                    'content_recommendations' => $cluster['content_recommendations'],
                    'best_posting_times' => $cluster['best_posting_times']
                ]
            );
        }

        return $clusters;
    }

    protected function calculatePeriods(string $periodType, Carbon $endDate): array
    {
        $current = [
            'end' => $endDate,
            'start' => match($periodType) {
                'week' => $endDate->copy()->subWeek(),
                'month' => $endDate->copy()->subMonth(),
                'quarter' => $endDate->copy()->subMonths(3),
                'year' => $endDate->copy()->subYear(),
                default => $endDate->copy()->subWeek()
            }
        ];

        $previous = [
            'end' => $current['start'],
            'start' => match($periodType) {
                'week' => $current['end']->copy()->subWeek(),
                'month' => $current['end']->copy()->subMonth(),
                'quarter' => $current['end']->copy()->subMonths(3),
                'year' => $current['end']->copy()->subYear(),
                default => $current['end']->copy()->subWeek()
            }
        ];

        return [
            'current' => $current,
            'previous' => $previous
        ];
    }

    protected function getMetricData(SocialAccount $account, string $metricType, array $period): array
    {
        return match($metricType) {
            'engagement' => $this->getEngagementData($account, $period),
            'growth' => $this->getGrowthData($account, $period),
            'demographics' => $this->getDemographicsData($account, $period),
            'behavior' => $this->getBehaviorData($account, $period),
            default => []
        };
    }

    protected function calculateChangePercentage(array $current, array $previous): float
    {
        if (empty($previous)) {
            return 0;
        }

        $currentValue = $this->calculateMetricValue($current);
        $previousValue = $this->calculateMetricValue($previous);

        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }

        return (($currentValue - $previousValue) / $previousValue) * 100;
    }

    protected function calculateMetricValue(array $data): float
    {
        if (isset($data['value'])) {
            return (float) $data['value'];
        }

        if (isset($data['total'])) {
            return (float) $data['total'];
        }

        return 0;
    }

    protected function generateComparisonInsights(array $current, array $previous, string $metricType): array
    {
        $insights = [];
        $changePercentage = $this->calculateChangePercentage($current, $previous);

        // Generate insights based on metric type and change
        switch ($metricType) {
            case 'engagement':
                $insights = $this->generateEngagementInsights($current, $previous, $changePercentage);
                break;
            case 'growth':
                $insights = $this->generateGrowthInsights($current, $previous, $changePercentage);
                break;
            case 'demographics':
                $insights = $this->generateDemographicsInsights($current, $previous);
                break;
            case 'behavior':
                $insights = $this->generateBehaviorInsights($current, $previous);
                break;
        }

        return $insights;
    }

    protected function generateEngagementInsights(array $current, array $previous, float $changePercentage): array
    {
        $insights = [];

        if ($changePercentage > 10) {
            $insights[] = 'تحسن كبير في معدل التفاعل';
        } elseif ($changePercentage > 0) {
            $insights[] = 'تحسن طفيف في معدل التفاعل';
        } elseif ($changePercentage < -10) {
            $insights[] = 'انخفاض كبير في معدل التفاعل';
        } elseif ($changePercentage < 0) {
            $insights[] = 'انخفاض طفيف في معدل التفاعل';
        }

        // Compare engagement by content type
        if (isset($current['by_type']) && isset($previous['by_type'])) {
            foreach ($current['by_type'] as $type => $value) {
                if (isset($previous['by_type'][$type])) {
                    $typeChange = (($value - $previous['by_type'][$type]) / $previous['by_type'][$type]) * 100;
                    if ($typeChange > 20) {
                        $insights[] = "تحسن كبير في التفاعل مع محتوى {$type}";
                    } elseif ($typeChange < -20) {
                        $insights[] = "انخفاض كبير في التفاعل مع محتوى {$type}";
                    }
                }
            }
        }

        return $insights;
    }

    protected function generateGrowthInsights(array $current, array $previous, float $changePercentage): array
    {
        $insights = [];

        if ($changePercentage > 5) {
            $insights[] = 'نمو قوي في عدد المتابعين';
        } elseif ($changePercentage > 0) {
            $insights[] = 'نمو طفيف في عدد المتابعين';
        } elseif ($changePercentage < -5) {
            $insights[] = 'انخفاض ملحوظ في عدد المتابعين';
        } elseif ($changePercentage < 0) {
            $insights[] = 'انخفاض طفيف في عدد المتابعين';
        }

        // Analyze follower acquisition channels
        if (isset($current['acquisition_channels']) && isset($previous['acquisition_channels'])) {
            foreach ($current['acquisition_channels'] as $channel => $value) {
                if (isset($previous['acquisition_channels'][$channel])) {
                    $channelChange = (($value - $previous['acquisition_channels'][$channel]) / $previous['acquisition_channels'][$channel]) * 100;
                    if ($channelChange > 30) {
                        $insights[] = "زيادة كبيرة في المتابعين من {$channel}";
                    } elseif ($channelChange < -30) {
                        $insights[] = "انخفاض كبير في المتابعين من {$channel}";
                    }
                }
            }
        }

        return $insights;
    }

    protected function generateDemographicsInsights(array $current, array $previous): array
    {
        $insights = [];

        // Compare age distribution
        if (isset($current['age_distribution']) && isset($previous['age_distribution'])) {
            foreach ($current['age_distribution'] as $age => $percentage) {
                if (isset($previous['age_distribution'][$age])) {
                    $change = $percentage - $previous['age_distribution'][$age];
                    if ($change > 5) {
                        $insights[] = "زيادة في نسبة المتابعين في الفئة العمرية {$age}";
                    } elseif ($change < -5) {
                        $insights[] = "انخفاض في نسبة المتابعين في الفئة العمرية {$age}";
                    }
                }
            }
        }

        // Compare gender distribution
        if (isset($current['gender_distribution']) && isset($previous['gender_distribution'])) {
            foreach ($current['gender_distribution'] as $gender => $percentage) {
                if (isset($previous['gender_distribution'][$gender])) {
                    $change = $percentage - $previous['gender_distribution'][$gender];
                    if ($change > 5) {
                        $insights[] = "زيادة في نسبة المتابعين من {$gender}";
                    } elseif ($change < -5) {
                        $insights[] = "انخفاض في نسبة المتابعين من {$gender}";
                    }
                }
            }
        }

        return $insights;
    }

    protected function generateBehaviorInsights(array $current, array $previous): array
    {
        $insights = [];

        // Compare active hours
        if (isset($current['active_hours']) && isset($previous['active_hours'])) {
            foreach ($current['active_hours'] as $hour => $percentage) {
                if (isset($previous['active_hours'][$hour])) {
                    $change = $percentage - $previous['active_hours'][$hour];
                    if ($change > 10) {
                        $insights[] = "زيادة في النشاط في الساعة {$hour}";
                    } elseif ($change < -10) {
                        $insights[] = "انخفاض في النشاط في الساعة {$hour}";
                    }
                }
            }
        }

        // Compare content preferences
        if (isset($current['content_preferences']) && isset($previous['content_preferences'])) {
            foreach ($current['content_preferences'] as $preference => $percentage) {
                if (isset($previous['content_preferences'][$preference])) {
                    $change = $percentage - $previous['content_preferences'][$preference];
                    if ($change > 10) {
                        $insights[] = "زيادة في تفضيل محتوى {$preference}";
                    } elseif ($change < -10) {
                        $insights[] = "انخفاض في تفضيل محتوى {$preference}";
                    }
                }
            }
        }

        return $insights;
    }

    protected function generatePersonaName(array $demographics, array $behaviors): string
    {
        $age = $demographics['age_range'] ?? '25-34';
        $gender = $demographics['gender'] ?? 'مختلط';
        $activity = $behaviors['activity_level'] ?? 'نشط';

        return "{$gender} {$age} {$activity}";
    }

    protected function generatePersonaDescription(
        array $demographics,
        array $behaviors,
        array $contentPreferences
    ): string {
        $description = "شخصية " . ($demographics['gender'] ?? 'مختلطة') . " في الفئة العمرية " . ($demographics['age_range'] ?? '25-34') . " ";

        if (isset($demographics['location'])) {
            $description .= "من {$demographics['location']} ";
        }

        $description .= "مع مستوى نشاط " . ($behaviors['activity_level'] ?? 'متوسط') . ". ";

        if (!empty($contentPreferences['top_categories'])) {
            $description .= "يهتم بـ " . implode(', ', array_slice($contentPreferences['top_categories'], 0, 3)) . ". ";
        }

        if (isset($behaviors['engagement_frequency'])) {
            $description .= "يتفاعل مع المحتوى {$behaviors['engagement_frequency']}. ";
        }

        return $description;
    }

    protected function analyzeInterests(array $contentPreferences, array $behaviors): array
    {
        $interests = [];

        // Add content category interests
        if (isset($contentPreferences['top_categories'])) {
            $interests = array_merge($interests, $contentPreferences['top_categories']);
        }

        // Add topic interests based on engagement
        if (isset($behaviors['top_topics'])) {
            $interests = array_merge($interests, $behaviors['top_topics']);
        }

        // Add platform-specific interests
        if (isset($behaviors['platform_preferences'])) {
            foreach ($behaviors['platform_preferences'] as $platform => $preferences) {
                if (isset($preferences['interests'])) {
                    $interests = array_merge($interests, $preferences['interests']);
                }
            }
        }

        return array_unique($interests);
    }

    protected function analyzePainPoints(array $behaviors, array $contentPreferences): array
    {
        $painPoints = [];

        // Analyze engagement patterns
        if (isset($behaviors['engagement_patterns'])) {
            foreach ($behaviors['engagement_patterns'] as $pattern) {
                if (isset($pattern['pain_point'])) {
                    $painPoints[] = $pattern['pain_point'];
                }
            }
        }

        // Analyze content preferences
        if (isset($contentPreferences['avoided_categories'])) {
            foreach ($contentPreferences['avoided_categories'] as $category) {
                $painPoints[] = "عدم الاهتمام بمحتوى {$category}";
            }
        }

        // Analyze platform behavior
        if (isset($behaviors['platform_preferences'])) {
            foreach ($behaviors['platform_preferences'] as $platform => $preferences) {
                if (isset($preferences['pain_points'])) {
                    $painPoints = array_merge($painPoints, $preferences['pain_points']);
                }
            }
        }

        return array_unique($painPoints);
    }

    protected function analyzeGoals(array $behaviors, array $contentPreferences): array
    {
        $goals = [];

        // Analyze content engagement goals
        if (isset($contentPreferences['engagement_goals'])) {
            $goals = array_merge($goals, $contentPreferences['engagement_goals']);
        }

        // Analyze platform-specific goals
        if (isset($behaviors['platform_preferences'])) {
            foreach ($behaviors['platform_preferences'] as $platform => $preferences) {
                if (isset($preferences['goals'])) {
                    $goals = array_merge($goals, $preferences['goals']);
                }
            }
        }

        // Add inferred goals based on behavior
        if (isset($behaviors['activity_level'])) {
            switch ($behaviors['activity_level']) {
                case 'نشط جداً':
                    $goals[] = 'البحث عن محتوى جديد ومتنوع';
                    $goals[] = 'التفاعل مع المجتمع';
                    break;
                case 'نشط':
                    $goals[] = 'البحث عن معلومات محددة';
                    $goals[] = 'متابعة التحديثات';
                    break;
                case 'متوسط':
                    $goals[] = 'البقاء على اطلاع';
                    $goals[] = 'التفاعل عند الحاجة';
                    break;
                case 'منخفض':
                    $goals[] = 'متابعة المحتوى الأساسي فقط';
                    $goals[] = 'التفاعل المحدود';
                    break;
            }
        }

        return array_unique($goals);
    }

    protected function analyzeBrandInteractions(array $insights): array
    {
        $interactions = [];

        // Analyze brand mentions
        if (isset($insights['brand_mentions'])) {
            $interactions['mentions'] = $insights['brand_mentions'];
        }

        // Analyze sentiment
        if (isset($insights['sentiment'])) {
            $interactions['sentiment'] = $insights['sentiment'];
        }

        // Analyze engagement types
        if (isset($insights['engagement_types'])) {
            $interactions['engagement_types'] = $insights['engagement_types'];
        }

        // Analyze purchase intent
        if (isset($insights['purchase_intent'])) {
            $interactions['purchase_intent'] = $insights['purchase_intent'];
        }

        return $interactions;
    }

    protected function calculateEngagementRate(array $insights): float
    {
        if (!isset($insights['total_engagement']) || !isset($insights['total_reach'])) {
            return 0;
        }

        if ($insights['total_reach'] == 0) {
            return 0;
        }

        return ($insights['total_engagement'] / $insights['total_reach']) * 100;
    }

    protected function estimatePersonaSize(array $clusterData): int
    {
        if (!isset($clusterData['size'])) {
            return 0;
        }

        return (int) $clusterData['size'];
    }

    protected function generatePersonaRecommendations(
        array $interests,
        array $painPoints,
        array $goals,
        array $contentPreferences
    ): array {
        $recommendations = [];

        // Content recommendations based on interests
        foreach ($interests as $interest) {
            $recommendations[] = "إنشاء محتوى يركز على {$interest}";
        }

        // Content recommendations based on pain points
        foreach ($painPoints as $painPoint) {
            $recommendations[] = "معالجة {$painPoint} من خلال المحتوى";
        }

        // Content recommendations based on goals
        foreach ($goals as $goal) {
            $recommendations[] = "تطوير محتوى يدعم {$goal}";
        }

        // Timing recommendations based on content preferences
        if (isset($contentPreferences['best_posting_times'])) {
            foreach ($contentPreferences['best_posting_times'] as $time) {
                $recommendations[] = "النشر في {$time}";
            }
        }

        // Format recommendations based on content preferences
        if (isset($contentPreferences['preferred_formats'])) {
            foreach ($contentPreferences['preferred_formats'] as $format) {
                $recommendations[] = "استخدام تنسيق {$format}";
            }
        }

        return array_unique($recommendations);
    }
} 