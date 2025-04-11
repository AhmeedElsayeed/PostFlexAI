<?php

namespace App\Services;

use App\Models\ContentIdea;
use App\Models\AudienceCluster;
use App\Models\AudiencePersona;
use App\Models\Team;
use Illuminate\Support\Collection;
use OpenAI\Client;

class ContentIdeaService
{
    protected $openai;

    public function __construct(Client $openai)
    {
        $this->openai = $openai;
    }

    public function generateIdeas(Team $team, ?AudienceCluster $segment = null, ?AudiencePersona $persona = null)
    {
        $context = $this->buildContext($team, $segment, $persona);
        $prompt = $this->buildPrompt($context);
        
        $response = $this->openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'أنت مساعد محتوى متخصص في إنشاء أفكار محتوى مخصصة للجمهور المستهدف.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ]);

        $ideas = $this->parseIdeas($response->choices[0]->message->content);
        
        return $this->saveIdeas($team, $ideas, $segment, $persona);
    }

    protected function buildContext(Team $team, ?AudienceCluster $segment, ?AudiencePersona $persona): array
    {
        $context = [
            'team_name' => $team->name,
            'team_industry' => $team->industry,
            'team_goals' => $team->goals
        ];

        if ($segment) {
            $context['segment'] = [
                'name' => $segment->name,
                'characteristics' => $segment->characteristics,
                'content_preferences' => $segment->content_preferences,
                'best_posting_time' => $segment->best_posting_time
            ];
        }

        if ($persona) {
            $context['persona'] = [
                'name' => $persona->name,
                'description' => $persona->description,
                'interests' => $persona->interests,
                'pain_points' => $persona->pain_points,
                'goals' => $persona->goals
            ];
        }

        return $context;
    }

    protected function buildPrompt(array $context): string
    {
        $prompt = "قم بإنشاء 5 أفكار محتوى مخصصة للفريق {$context['team_name']} في مجال {$context['team_industry']}.\n\n";

        if (isset($context['segment'])) {
            $prompt .= "الجمهور المستهدف: {$context['segment']['name']}\n";
            $prompt .= "الخصائص: " . implode(', ', $context['segment']['characteristics']) . "\n";
            $prompt .= "تفضيلات المحتوى: " . implode(', ', $context['segment']['content_preferences']) . "\n";
        }

        if (isset($context['persona'])) {
            $prompt .= "\nشخصية الجمهور: {$context['persona']['name']}\n";
            $prompt .= "الوصف: {$context['persona']['description']}\n";
            $prompt .= "الاهتمامات: " . implode(', ', $context['persona']['interests']) . "\n";
            $prompt .= "المشكلات: " . implode(', ', $context['persona']['pain_points']) . "\n";
            $prompt .= "الأهداف: " . implode(', ', $context['persona']['goals']) . "\n";
        }

        $prompt .= "\nقم بتقديم الأفكار بالتنسيق التالي:\n";
        $prompt .= "1. عنوان الفكرة\n";
        $prompt .= "2. وصف مختصر\n";
        $prompt .= "3. نوع المحتوى المقترح\n";
        $prompt .= "4. الوقت المقترح للنشر\n";
        $prompt .= "5. معدل التفاعل المتوقع\n";

        return $prompt;
    }

    protected function parseIdeas(string $response): Collection
    {
        $ideas = collect();
        $lines = explode("\n", $response);
        $currentIdea = [];

        foreach ($lines as $line) {
            if (preg_match('/^\d+\./', $line)) {
                if (!empty($currentIdea)) {
                    $ideas->push($currentIdea);
                }
                $currentIdea = ['title' => trim(substr($line, strpos($line, '.') + 1))];
            } elseif (!empty($line) && !empty($currentIdea)) {
                if (!isset($currentIdea['description'])) {
                    $currentIdea['description'] = trim($line);
                } elseif (!isset($currentIdea['type'])) {
                    $currentIdea['type'] = trim($line);
                } elseif (!isset($currentIdea['suggested_time'])) {
                    $currentIdea['suggested_time'] = trim($line);
                } elseif (!isset($currentIdea['expected_engagement'])) {
                    $currentIdea['expected_engagement'] = trim($line);
                }
            }
        }

        if (!empty($currentIdea)) {
            $ideas->push($currentIdea);
        }

        return $ideas;
    }

    protected function saveIdeas(Team $team, Collection $ideas, ?AudienceCluster $segment, ?AudiencePersona $persona): Collection
    {
        return $ideas->map(function ($idea) use ($team, $segment, $persona) {
            return ContentIdea::create([
                'team_id' => $team->id,
                'title' => $idea['title'],
                'description' => $idea['description'],
                'type' => $idea['type'],
                'suggested_time' => $idea['suggested_time'],
                'expected_engagement' => $idea['expected_engagement'],
                'segment_id' => $segment?->id,
                'persona_id' => $persona?->id
            ]);
        });
    }
} 