<?php

namespace App\Services;

use App\Models\AudienceInsight;
use App\Models\AudienceCluster;
use App\Models\SocialAccount;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIAudienceAnalysisService
{
    protected $openaiApiKey;
    protected $openaiEndpoint = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->openaiApiKey = config('services.openai.api_key');
    }

    public function analyzeAudienceBehavior(AudienceInsight $insight)
    {
        try {
            $prompt = $this->generateAnalysisPrompt($insight);
            $response = $this->callOpenAI($prompt);
            
            return $this->processAIResponse($response);
        } catch (\Exception $e) {
            Log::error('AI Analysis failed: ' . $e->getMessage());
            return null;
        }
    }

    public function detectBehavioralChanges(AudienceInsight $current, AudienceInsight $previous)
    {
        try {
            $prompt = $this->generateChangeDetectionPrompt($current, $previous);
            $response = $this->callOpenAI($prompt);
            
            return $this->processChangeDetectionResponse($response);
        } catch (\Exception $e) {
            Log::error('Behavioral Change Detection failed: ' . $e->getMessage());
            return null;
        }
    }

    public function generateContentRecommendations(AudienceCluster $cluster)
    {
        try {
            $prompt = $this->generateRecommendationsPrompt($cluster);
            $response = $this->callOpenAI($prompt);
            
            return $this->processRecommendationsResponse($response);
        } catch (\Exception $e) {
            Log::error('Content Recommendations failed: ' . $e->getMessage());
            return null;
        }
    }

    protected function generateAnalysisPrompt(AudienceInsight $insight)
    {
        return "Analyze the following audience data and provide insights about their behavior, preferences, and engagement patterns:\n\n" .
               "Demographics: " . json_encode($insight->demographics) . "\n" .
               "Interests: " . json_encode($insight->interests) . "\n" .
               "Active Hours: " . json_encode($insight->top_active_hours) . "\n" .
               "Content Preferences: " . json_encode($insight->content_preferences) . "\n" .
               "Engagement Metrics: " . json_encode($insight->engagement_metrics) . "\n" .
               "Growth Metrics: " . json_encode($insight->growth_metrics);
    }

    protected function generateChangeDetectionPrompt(AudienceInsight $current, AudienceInsight $previous)
    {
        return "Compare these two sets of audience data and identify significant changes in behavior:\n\n" .
               "Previous Data:\n" . json_encode($previous->toArray()) . "\n\n" .
               "Current Data:\n" . json_encode($current->toArray());
    }

    protected function generateRecommendationsPrompt(AudienceCluster $cluster)
    {
        return "Based on this audience cluster's characteristics, generate content recommendations:\n\n" .
               "Cluster Name: {$cluster->name}\n" .
               "Size: {$cluster->size}\n" .
               "Engagement Rate: {$cluster->engagement_rate}%\n" .
               "Characteristics: " . json_encode($cluster->characteristics) . "\n" .
               "Content Preferences: " . json_encode($cluster->content_recommendations);
    }

    protected function callOpenAI($prompt)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->openaiApiKey,
            'Content-Type' => 'application/json',
        ])->post($this->openaiEndpoint, [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert in social media audience analysis and content strategy.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API call failed: ' . $response->body());
        }

        return $response->json();
    }

    protected function processAIResponse($response)
    {
        if (!isset($response['choices'][0]['message']['content'])) {
            throw new \Exception('Invalid AI response format');
        }

        $content = $response['choices'][0]['message']['content'];
        return json_decode($content, true);
    }

    protected function processChangeDetectionResponse($response)
    {
        $analysis = $this->processAIResponse($response);
        
        return [
            'significant_changes' => $analysis['significant_changes'] ?? [],
            'trends' => $analysis['trends'] ?? [],
            'recommendations' => $analysis['recommendations'] ?? []
        ];
    }

    protected function processRecommendationsResponse($response)
    {
        $analysis = $this->processAIResponse($response);
        
        return [
            'content_types' => $analysis['content_types'] ?? [],
            'topics' => $analysis['topics'] ?? [],
            'posting_schedule' => $analysis['posting_schedule'] ?? [],
            'engagement_strategies' => $analysis['engagement_strategies'] ?? []
        ];
    }
} 