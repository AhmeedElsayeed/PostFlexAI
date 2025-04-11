<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\MarketingGoalService;

class MarketingGoalController extends Controller
{
    protected $marketingGoalService;

    public function __construct(MarketingGoalService $marketingGoalService)
    {
        $this->marketingGoalService = $marketingGoalService;
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'target_value' => 'required|numeric',
            'current_value' => 'required|numeric',
            'deadline' => 'required|date',
            'metrics' => 'required|array',
            'platforms' => 'required|array'
        ]);

        $goal = $this->marketingGoalService->createGoal($request->all());
        return response()->json($goal);
    }

    public function index()
    {
        $goals = $this->marketingGoalService->getAllGoals();
        return response()->json($goals);
    }

    public function getProgress()
    {
        $progress = $this->marketingGoalService->getGoalsProgress();
        return response()->json($progress);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'target_value' => 'sometimes|numeric',
            'current_value' => 'sometimes|numeric',
            'deadline' => 'sometimes|date',
            'metrics' => 'sometimes|array',
            'platforms' => 'sometimes|array'
        ]);

        $goal = $this->marketingGoalService->updateGoal($id, $request->all());
        return response()->json($goal);
    }

    public function destroy($id)
    {
        $this->marketingGoalService->deleteGoal($id);
        return response()->json(['message' => 'Goal deleted successfully']);
    }

    public function getRecommendations($id)
    {
        $recommendations = $this->marketingGoalService->getGoalRecommendations($id);
        return response()->json($recommendations);
    }
} 