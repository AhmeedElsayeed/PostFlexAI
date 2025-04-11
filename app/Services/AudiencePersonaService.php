<?php

namespace App\Services;

use App\Models\AudiencePersona;
use App\Models\Client;
use App\Models\Team;
use App\Models\SocialAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AudiencePersonaService
{
    protected $openaiService;

    public function __construct(OpenAIService $openaiService)
    {
        $this->openaiService = $openaiService;
    }

    public function generatePersonas(Team $team, SocialAccount $account = null): Collection
    {
        try {
            // Get client data for analysis
            $clients = $account 
                ? $account->clients()->with('messages', 'interactions')->get()
                : $team->clients()->with('messages', 'interactions')->get();

            // Analyze client data to identify patterns
            $patterns = $this->analyzeClientPatterns($clients);

            // Generate personas based on patterns
            $personas = collect();
            foreach ($patterns as $pattern) {
                $persona = $this->createPersonaFromPattern($team, $account, $pattern);
                $personas->push($persona);
            }

            return $personas;
        } catch (\Exception $e) {
            Log::error('Error generating personas: ' . $e->getMessage());
            return collect();
        }
    }

    protected function analyzeClientPatterns(Collection $clients): array
    {
        $patterns = [];

        // Group clients by age range
        $ageGroups = $clients->groupBy(function ($client) {
            return $this->getAgeRange($client->age);
        });

        // Group clients by interests
        $interestGroups = $clients->groupBy(function ($client) {
            return $client->interests;
        });

        // Group clients by location
        $locationGroups = $clients->groupBy('location');

        // Analyze engagement patterns
        $engagementPatterns = $this->analyzeEngagementPatterns($clients);

        // Combine patterns
        foreach ($ageGroups as $ageRange => $ageClients) {
            foreach ($interestGroups as $interests => $interestClients) {
                $intersection = $ageClients->intersect($interestClients);
                if ($intersection->count() >= 5) { // Minimum threshold for a persona
                    $patterns[] = [
                        'age_range' => $ageRange,
                        'interests' => $interests,
                        'engagement' => $this->calculateAverageEngagement($intersection),
                        'content_preferences' => $this->analyzeContentPreferences($intersection),
                        'active_hours' => $this->analyzeActiveHours($intersection),
                        'count' => $intersection->count()
                    ];
                }
            }
        }

        return $patterns;
    }

    protected function createPersonaFromPattern(Team $team, ?SocialAccount $account, array $pattern): AudiencePersona
    {
        // Generate persona name using AI
        $name = $this->generatePersonaName($pattern);

        // Generate avatar URL (placeholder for now)
        $avatar = $this->generateAvatarUrl($pattern);

        // Create persona
        return AudiencePersona::create([
            'team_id' => $team->id,
            'social_account_id' => $account?->id,
            'name' => $name,
            'avatar' => $avatar,
            'age_range_start' => $pattern['age_range'][0],
            'age_range_end' => $pattern['age_range'][1],
            'interests' => $pattern['interests'],
            'preferred_content_types' => $pattern['content_preferences'],
            'active_hours' => $pattern['active_hours'],
            'engagement_rate' => $pattern['engagement'],
            'description' => $this->generatePersonaDescription($pattern),
            'is_auto_generated' => true
        ]);
    }

    protected function generatePersonaName(array $pattern): string
    {
        $prompt = "Generate a friendly, memorable name for a social media persona with the following characteristics:\n";
        $prompt .= "Age Range: {$pattern['age_range'][0]}-{$pattern['age_range'][1]}\n";
        $prompt .= "Interests: " . implode(", ", $pattern['interests']) . "\n";
        $prompt .= "Location: " . ($pattern['location'] ?? 'Various') . "\n";
        $prompt .= "The name should be culturally appropriate and easy to remember.";

        try {
            $response = $this->openaiService->complete($prompt);
            return trim($response);
        } catch (\Exception $e) {
            Log::error('Error generating persona name: ' . $e->getMessage());
            return "Persona " . uniqid();
        }
    }

    protected function generateAvatarUrl(array $pattern): string
    {
        // TODO: Implement avatar generation using AI or a service like DiceBear
        return "https://api.dicebear.com/7.x/avataaars/svg?seed=" . md5(json_encode($pattern));
    }

    protected function generatePersonaDescription(array $pattern): string
    {
        $prompt = "Generate a concise, engaging description for a social media persona with the following characteristics:\n";
        $prompt .= "Age Range: {$pattern['age_range'][0]}-{$pattern['age_range'][1]}\n";
        $prompt .= "Interests: " . implode(", ", $pattern['interests']) . "\n";
        $prompt .= "Content Preferences: " . implode(", ", $pattern['content_preferences']) . "\n";
        $prompt .= "Active Hours: " . implode(", ", $pattern['active_hours']) . "\n";
        $prompt .= "The description should be professional and highlight key characteristics.";

        try {
            $response = $this->openaiService->complete($prompt);
            return trim($response);
        } catch (\Exception $e) {
            Log::error('Error generating persona description: ' . $e->getMessage());
            return "A social media user interested in " . implode(", ", $pattern['interests']);
        }
    }

    protected function getAgeRange(?int $age): array
    {
        if (!$age) return [18, 65];
        
        $start = floor($age / 5) * 5;
        return [$start, $start + 4];
    }

    protected function calculateAverageEngagement(Collection $clients): float
    {
        if ($clients->isEmpty()) return 0;

        $totalEngagement = $clients->sum(function ($client) {
            return $client->interactions->count() / max(1, $client->messages->count());
        });

        return round($totalEngagement / $clients->count(), 2);
    }

    protected function analyzeContentPreferences(Collection $clients): array
    {
        $preferences = [];
        
        foreach ($clients as $client) {
            foreach ($client->interactions as $interaction) {
                $type = $interaction->content_type;
                $preferences[$type] = ($preferences[$type] ?? 0) + 1;
            }
        }

        arsort($preferences);
        return array_keys(array_slice($preferences, 0, 5));
    }

    protected function analyzeActiveHours(Collection $clients): array
    {
        $hours = array_fill(0, 24, 0);
        
        foreach ($clients as $client) {
            foreach ($client->interactions as $interaction) {
                $hour = (int) $interaction->created_at->format('H');
                $hours[$hour]++;
            }
        }

        arsort($hours);
        return array_keys(array_slice($hours, 0, 5));
    }

    protected function analyzeEngagementPatterns(Collection $clients): array
    {
        $patterns = [
            'peak_days' => [],
            'peak_hours' => [],
            'content_types' => [],
            'response_times' => []
        ];

        foreach ($clients as $client) {
            // Analyze peak days
            $dayCounts = $client->interactions
                ->groupBy(function ($interaction) {
                    return $interaction->created_at->format('l');
                })
                ->map->count();
            
            foreach ($dayCounts as $day => $count) {
                $patterns['peak_days'][$day] = ($patterns['peak_days'][$day] ?? 0) + $count;
            }

            // Analyze response times
            $responseTimes = $client->messages
                ->map(function ($message) {
                    return $message->response_time;
                })
                ->filter();
            
            if ($responseTimes->isNotEmpty()) {
                $patterns['response_times'][] = $responseTimes->avg();
            }
        }

        // Sort and get top patterns
        arsort($patterns['peak_days']);
        $patterns['peak_days'] = array_slice($patterns['peak_days'], 0, 3);
        
        if (!empty($patterns['response_times'])) {
            $patterns['average_response_time'] = array_sum($patterns['response_times']) / count($patterns['response_times']);
        }

        return $patterns;
    }
} 