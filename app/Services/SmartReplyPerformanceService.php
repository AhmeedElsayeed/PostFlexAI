<?php

namespace App\Services;

use App\Models\SmartReplyTemplate;
use App\Models\MessageAnalysis;
use Illuminate\Support\Facades\DB;

class SmartReplyPerformanceService
{
    public function getTemplatePerformance(SmartReplyTemplate $template)
    {
        return [
            'total_uses' => $template->total_uses,
            'success_rate' => $template->success_rate,
            'average_response_time' => $this->calculateAverageResponseTime($template),
            'category_performance' => $this->getCategoryPerformance($template),
            'time_based_performance' => $this->getTimeBasedPerformance($template),
            'improvement_suggestions' => $this->generateImprovementSuggestions($template)
        ];
    }

    public function getOverallPerformance()
    {
        return [
            'total_templates' => SmartReplyTemplate::count(),
            'average_success_rate' => SmartReplyTemplate::avg('success_rate'),
            'top_performing_templates' => $this->getTopPerformingTemplates(),
            'category_distribution' => $this->getCategoryDistribution(),
            'performance_trends' => $this->getPerformanceTrends()
        ];
    }

    private function calculateAverageResponseTime(SmartReplyTemplate $template)
    {
        return MessageAnalysis::where('template_id', $template->id)
            ->whereNotNull('response_time')
            ->avg('response_time');
    }

    private function getCategoryPerformance(SmartReplyTemplate $template)
    {
        return MessageAnalysis::where('template_id', $template->id)
            ->select('category', DB::raw('COUNT(*) as total'), DB::raw('AVG(success_rate) as avg_success'))
            ->groupBy('category')
            ->get();
    }

    private function getTimeBasedPerformance(SmartReplyTemplate $template)
    {
        return MessageAnalysis::where('template_id', $template->id)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total_uses'),
                DB::raw('AVG(success_rate) as avg_success')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
    }

    private function getTopPerformingTemplates()
    {
        return SmartReplyTemplate::where('total_uses', '>', 0)
            ->orderBy('success_rate', 'desc')
            ->limit(5)
            ->get(['id', 'content', 'category', 'success_rate', 'total_uses']);
    }

    private function getCategoryDistribution()
    {
        return SmartReplyTemplate::select('category', DB::raw('COUNT(*) as total'))
            ->groupBy('category')
            ->get();
    }

    private function getPerformanceTrends()
    {
        return MessageAnalysis::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as total_messages'),
            DB::raw('AVG(success_rate) as avg_success_rate')
        )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
    }

    private function generateImprovementSuggestions(SmartReplyTemplate $template)
    {
        $suggestions = [];

        // Check success rate
        if ($template->success_rate < 0.7) {
            $suggestions[] = [
                'type' => 'success_rate',
                'message' => 'معدل النجاح منخفض. يُقترح مراجعة محتوى القالب وتحسينه.',
                'priority' => 'high'
            ];
        }

        // Check response time
        $avgResponseTime = $this->calculateAverageResponseTime($template);
        if ($avgResponseTime > 300) { // 5 minutes
            $suggestions[] = [
                'type' => 'response_time',
                'message' => 'وقت الاستجابة طويل. يُقترح تبسيط القالب.',
                'priority' => 'medium'
            ];
        }

        // Check usage frequency
        if ($template->total_uses < 10) {
            $suggestions[] = [
                'type' => 'usage',
                'message' => 'القالب قليل الاستخدام. يُقترح تحسين التوافق مع المزيد من الحالات.',
                'priority' => 'low'
            ];
        }

        return $suggestions;
    }

    public function updateTemplatePerformance(SmartReplyTemplate $template, bool $wasSuccessful, float $responseTime)
    {
        $template->total_uses++;
        $template->success_rate = (($template->success_rate * ($template->total_uses - 1)) + ($wasSuccessful ? 1 : 0)) / $template->total_uses;
        $template->save();

        // Log the performance data
        MessageAnalysis::create([
            'template_id' => $template->id,
            'success' => $wasSuccessful,
            'response_time' => $responseTime,
            'created_at' => now()
        ]);
    }
} 