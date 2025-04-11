<?php

namespace App\Http\Controllers;

use App\Models\SentimentAnalysis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SentimentController extends Controller
{
    public function analyze(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'type' => 'required|string|in:comment,message,post'
        ]);

        // Here we'll use a sentiment analysis API (e.g., Google Cloud Natural Language API)
        // This is a placeholder for the actual API call
        $response = Http::post('https://language.googleapis.com/v1/documents:analyzeSentiment', [
            'document' => [
                'type' => 'PLAIN_TEXT',
                'content' => $request->content
            ],
            'encodingType' => 'UTF8'
        ]);

        $sentiment = $response->json();

        $analysis = SentimentAnalysis::create([
            'analyzed_type' => $request->type,
            'sentiment_score' => $sentiment['documentSentiment']['score'],
            'sentiment_label' => $this->getSentimentLabel($sentiment['documentSentiment']['score']),
            'confidence_score' => $sentiment['documentSentiment']['magnitude'],
            'keywords' => $this->extractKeywords($request->content),
            'summary' => $this->generateSummary($request->content)
        ]);

        return response()->json([
            'message' => 'Sentiment analysis completed successfully',
            'data' => $analysis
        ]);
    }

    public function getStats()
    {
        $stats = SentimentAnalysis::selectRaw('
            sentiment_label,
            COUNT(*) as count,
            AVG(sentiment_score) as average_score
        ')
        ->groupBy('sentiment_label')
        ->get();

        return response()->json([
            'data' => $stats
        ]);
    }

    private function getSentimentLabel($score)
    {
        if ($score >= 0.25) return 'positive';
        if ($score <= -0.25) return 'negative';
        return 'neutral';
    }

    private function extractKeywords($content)
    {
        // Implement keyword extraction logic
        // This is a placeholder
        return ['keyword1', 'keyword2'];
    }

    private function generateSummary($content)
    {
        // Implement summary generation logic
        // This is a placeholder
        return substr($content, 0, 100) . '...';
    }
} 