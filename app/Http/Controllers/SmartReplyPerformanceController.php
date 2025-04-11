<?php

namespace App\Http\Controllers;

use App\Models\SmartReplyTemplate;
use App\Services\SmartReplyPerformanceService;
use Illuminate\Http\Request;

class SmartReplyPerformanceController extends Controller
{
    protected $performanceService;

    public function __construct(SmartReplyPerformanceService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    public function getTemplatePerformance(SmartReplyTemplate $template)
    {
        return response()->json([
            'success' => true,
            'data' => $this->performanceService->getTemplatePerformance($template)
        ]);
    }

    public function getOverallPerformance()
    {
        return response()->json([
            'success' => true,
            'data' => $this->performanceService->getOverallPerformance()
        ]);
    }

    public function updateTemplatePerformance(Request $request, SmartReplyTemplate $template)
    {
        $request->validate([
            'was_successful' => 'required|boolean',
            'response_time' => 'required|numeric|min:0'
        ]);

        $this->performanceService->updateTemplatePerformance(
            $template,
            $request->was_successful,
            $request->response_time
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث أداء القالب بنجاح'
        ]);
    }

    public function getImprovementSuggestions(SmartReplyTemplate $template)
    {
        $performance = $this->performanceService->getTemplatePerformance($template);
        
        return response()->json([
            'success' => true,
            'data' => $performance['improvement_suggestions']
        ]);
    }
} 