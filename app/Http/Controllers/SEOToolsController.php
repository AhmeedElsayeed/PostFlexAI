<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SEOToolsService;

class SEOToolsController extends Controller
{
    protected $seoToolsService;

    public function __construct(SEOToolsService $seoToolsService)
    {
        $this->seoToolsService = $seoToolsService;
    }

    public function analyzePost(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'platform' => 'required|string',
            'target_keywords' => 'nullable|array'
        ]);

        $analysis = $this->seoToolsService->analyzePost($request->all());
        return response()->json($analysis);
    }

    public function generateMeta(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'platform' => 'required|string',
            'target_keywords' => 'required|array',
            'max_length' => 'nullable|integer'
        ]);

        $meta = $this->seoToolsService->generateMeta($request->all());
        return response()->json($meta);
    }

    public function getSEOScore($postId)
    {
        $score = $this->seoToolsService->calculateSEOScore($postId);
        return response()->json($score);
    }

    public function getKeywordSuggestions(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'platform' => 'required|string',
            'max_suggestions' => 'nullable|integer'
        ]);

        $suggestions = $this->seoToolsService->getKeywordSuggestions($request->all());
        return response()->json($suggestions);
    }

    public function optimizeContent(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'target_keywords' => 'required|array',
            'platform' => 'required|string'
        ]);

        $optimizedContent = $this->seoToolsService->optimizeContent($request->all());
        return response()->json($optimizedContent);
    }
} 