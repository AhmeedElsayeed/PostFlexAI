<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PerformanceAlertService;

class PerformanceAlertController extends Controller
{
    protected $performanceAlertService;

    public function __construct(PerformanceAlertService $performanceAlertService)
    {
        $this->performanceAlertService = $performanceAlertService;
    }

    public function getLowPerformancePosts()
    {
        $posts = $this->performanceAlertService->getLowPerformancePosts();
        return response()->json($posts);
    }

    public function getImprovementSuggestions($postId)
    {
        $suggestions = $this->performanceAlertService->getImprovementSuggestions($postId);
        return response()->json($suggestions);
    }

    public function reschedulePost(Request $request, $postId)
    {
        $request->validate([
            'new_time' => 'required|date',
            'platforms' => 'required|array'
        ]);

        $result = $this->performanceAlertService->reschedulePost($postId, $request->all());
        return response()->json($result);
    }

    public function getAlertSettings()
    {
        $settings = $this->performanceAlertService->getAlertSettings();
        return response()->json($settings);
    }

    public function updateAlertSettings(Request $request)
    {
        $request->validate([
            'threshold' => 'required|numeric|min:0|max:100',
            'metrics' => 'required|array',
            'notification_channels' => 'required|array'
        ]);

        $settings = $this->performanceAlertService->updateAlertSettings($request->all());
        return response()->json($settings);
    }
} 