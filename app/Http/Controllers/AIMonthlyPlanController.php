<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AIMonthlyPlanService;

class AIMonthlyPlanController extends Controller
{
    protected $aiMonthlyPlanService;

    public function __construct(AIMonthlyPlanService $aiMonthlyPlanService)
    {
        $this->aiMonthlyPlanService = $aiMonthlyPlanService;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
            'niche' => 'required|string',
            'target_audience' => 'required|array',
            'content_types' => 'required|array'
        ]);

        $plan = $this->aiMonthlyPlanService->generatePlan($request->all());
        return response()->json($plan);
    }

    public function show($id)
    {
        $plan = $this->aiMonthlyPlanService->getPlan($id);
        return response()->json($plan);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'day' => 'required|integer|min:1|max:31',
            'content' => 'required|array'
        ]);

        $updatedPlan = $this->aiMonthlyPlanService->updateDay($id, $request->all());
        return response()->json($updatedPlan);
    }

    public function scheduleFromPlan(Request $request, $id)
    {
        $request->validate([
            'day' => 'required|integer|min:1|max:31',
            'platforms' => 'required|array'
        ]);

        $scheduledPosts = $this->aiMonthlyPlanService->scheduleFromPlan($id, $request->all());
        return response()->json($scheduledPosts);
    }
} 