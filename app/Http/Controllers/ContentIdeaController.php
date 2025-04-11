<?php

namespace App\Http\Controllers;

use App\Models\ContentIdea;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContentIdeaController extends Controller
{
    public function index(Request $request)
    {
        $team = $request->user()->currentTeam;
        
        $ideas = ContentIdea::where('team_id', $team->id)
            ->when($request->platform, function ($query) use ($request) {
                return $query->where('platform', $request->platform);
            })
            ->when($request->goal, function ($query) use ($request) {
                return $query->where('goal', $request->goal);
            })
            ->when($request->theme, function ($query) use ($request) {
                return $query->where('theme', $request->theme);
            })
            ->latest()
            ->paginate(10);

        return response()->json($ideas);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'platform' => 'required|string',
            'goal' => 'required|string',
            'theme' => 'required|string',
            'audience' => 'required|string',
        ]);

        try {
            $prompt = $this->buildPrompt($request);
            $suggestions = $this->getAISuggestions($prompt);

            $idea = ContentIdea::create([
                'team_id' => $request->user()->currentTeam->id,
                'title' => "AI Generated Content Ideas",
                'description' => "Generated based on platform: {$request->platform}, goal: {$request->goal}",
                'platform' => $request->platform,
                'goal' => $request->goal,
                'theme' => $request->theme,
                'suggestions' => $suggestions,
                'is_ai_generated' => true
            ]);

            return response()->json($idea);
        } catch (\Exception $e) {
            Log::error("Error generating content ideas: " . $e->getMessage());
            return response()->json(['error' => 'Failed to generate content ideas'], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
            'platform' => 'required|string',
            'goal' => 'required|string',
            'theme' => 'nullable|string',
        ]);

        $idea = ContentIdea::create([
            'team_id' => $request->user()->currentTeam->id,
            'title' => $request->title,
            'description' => $request->description,
            'platform' => $request->platform,
            'goal' => $request->goal,
            'theme' => $request->theme,
            'is_ai_generated' => false
        ]);

        return response()->json($idea, 201);
    }

    private function buildPrompt(Request $request)
    {
        return "Generate 5 content ideas for {$request->platform} platform targeting {$request->audience} audience. 
                Goal: {$request->goal}. Theme: {$request->theme}. 
                Include engaging captions and hashtag suggestions.";
    }

    private function getAISuggestions(string $prompt)
    {
        // Replace with actual AI service integration
        $response = Http::post(config('services.ai.endpoint'), [
            'prompt' => $prompt,
            'max_tokens' => 500
        ]);

        if ($response->successful()) {
            return $response->json()['suggestions'];
        }

        throw new \Exception('Failed to get AI suggestions');
    }
} 