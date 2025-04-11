<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Team;
use Illuminate\Support\Collection;
use OpenAI\Client as OpenAI;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class ClientService
{
    protected $openai;

    public function __construct(OpenAI $openai)
    {
        $this->openai = $openai;
    }

    public function getTeamStats($teamId)
    {
        $clients = Client::where('team_id', $teamId)->get();

        return [
            'total_clients' => $clients->count(),
            'by_status' => $clients->groupBy('status')
                ->map(fn($group) => $group->count()),
            'by_platform' => $clients->groupBy('platform')
                ->map(fn($group) => $group->count()),
            'active_clients' => $clients->filter(fn($client) => 
                $client->last_interaction_at && 
                $client->last_interaction_at->isAfter(now()->subDays(30))
            )->count(),
            'vip_clients' => $clients->where('status', 'vip')->count(),
            'new_clients' => $clients->where('status', 'new')->count(),
            'unresponsive_clients' => $clients->where('status', 'unresponsive')->count()
        ];
    }

    public function getRecommendations(Client $client)
    {
        $messages = $client->messages()->recent()->take(10)->get();
        $posts = $client->posts()->recent()->take(10)->get();
        $notes = $client->notes()->recent()->take(5)->get();

        $context = $this->buildContext($client, $messages, $posts, $notes);
        
        $recommendations = $this->analyzeWithAI($context);

        return [
            'content_recommendations' => $recommendations['content'] ?? [],
            'engagement_recommendations' => $recommendations['engagement'] ?? [],
            'follow_up_recommendations' => $recommendations['follow_up'] ?? [],
            'best_time_to_engage' => $this->calculateBestEngagementTime($messages),
            'interests' => $this->extractInterests($messages, $posts)
        ];
    }

    protected function buildContext($client, $messages, $posts, $notes)
    {
        return [
            'client_info' => [
                'name' => $client->name,
                'platform' => $client->platform,
                'status' => $client->status,
                'tags' => $client->tags,
                'last_interaction' => $client->last_interaction_at?->diffForHumans()
            ],
            'recent_messages' => $messages->map(fn($msg) => [
                'content' => $msg->content,
                'type' => $msg->type,
                'created_at' => $msg->created_at->format('Y-m-d H:i:s')
            ])->toArray(),
            'recent_posts' => $posts->map(fn($post) => [
                'content' => $post->content,
                'type' => $post->type,
                'engagement' => $post->engagement_stats,
                'created_at' => $post->created_at->format('Y-m-d H:i:s')
            ])->toArray(),
            'recent_notes' => $notes->map(fn($note) => [
                'content' => $note->note,
                'type' => $note->type,
                'created_at' => $note->created_at->format('Y-m-d H:i:s')
            ])->toArray()
        ];
    }

    protected function analyzeWithAI($context)
    {
        $prompt = "Based on the following client data, provide recommendations for:\n" .
                 "1. Content that would engage this client\n" .
                 "2. Engagement strategies\n" .
                 "3. Follow-up actions\n\n" .
                 "Client Data:\n" . json_encode($context, JSON_PRETTY_PRINT);

        $response = $this->openai->chat()->create([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a social media marketing expert providing personalized recommendations.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ]);

        return json_decode($response->choices[0]->message->content, true);
    }

    protected function calculateBestEngagementTime($messages)
    {
        if ($messages->isEmpty()) {
            return null;
        }

        $hourlyDistribution = $messages->groupBy(function ($message) {
            return $message->created_at->format('H');
        })->map(function ($group) {
            return $group->count();
        });

        $bestHour = $hourlyDistribution->sortDesc()->keys()->first();

        return [
            'hour' => (int) $bestHour,
            'day_of_week' => $messages->sortByDesc('created_at')
                ->first()
                ->created_at
                ->format('l')
        ];
    }

    protected function extractInterests($messages, $posts)
    {
        $interests = collect();

        // Extract interests from messages
        $messages->each(function ($message) use ($interests) {
            $words = str_word_count(strtolower($message->content), 1);
            $interests->push(...$words);
        });

        // Extract interests from post interactions
        $posts->each(function ($post) use ($interests) {
            if ($post->content) {
                $words = str_word_count(strtolower($post->content), 1);
                $interests->push(...$words);
            }
        });

        // Count frequency and get top interests
        return $interests->countBy()
            ->sortDesc()
            ->take(10)
            ->keys()
            ->toArray();
    }

    public function exportToCsv($clients)
    {
        $csv = Writer::createFromString('');
        
        // Add headers
        $csv->insertOne([
            'ID',
            'Name',
            'Username',
            'Platform',
            'Status',
            'Email',
            'Phone',
            'Location',
            'Tags',
            'Last Interaction',
            'Total Messages',
            'Total Notes',
            'Created At'
        ]);

        // Add client data
        foreach ($clients as $client) {
            $csv->insertOne([
                $client->id,
                $client->name,
                $client->username,
                $client->platform,
                $client->status,
                $client->email,
                $client->phone,
                $client->location,
                implode(', ', $client->tags ?? []),
                $client->last_interaction_at?->format('Y-m-d H:i:s'),
                $client->messages()->count(),
                $client->notes()->count(),
                $client->created_at->format('Y-m-d H:i:s')
            ]);
        }

        $filename = 'clients_export_' . now()->format('Y-m-d_His') . '.csv';
        Storage::put('exports/' . $filename, $csv->toString());

        return response()->download(
            Storage::path('exports/' . $filename),
            $filename,
            ['Content-Type' => 'text/csv']
        )->deleteFileAfterSend();
    }
} 