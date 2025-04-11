<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageAnalysis;
use App\Models\SmartReplyTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SmartReplyService
{
    /**
     * Generate a smart reply for a given message
     *
     * @param Message $message
     * @return string|null
     */
    public function generateReply(Message $message): ?string
    {
        try {
            // Get the message content and context
            $content = $message->content;
            $context = $this->getMessageContext($message);

            // Find matching template
            $template = $this->findMatchingTemplate($content, $context);

            if (!$template) {
                return null;
            }

            // Generate personalized reply
            return $this->personalizeReply($template, $context);
        } catch (\Exception $e) {
            Log::error('Error generating smart reply: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all reply templates
     *
     * @return array
     */
    public function getTemplates(): array
    {
        return Cache::remember('smart_reply_templates', 3600, function () {
            return SmartReplyTemplate::all()->map(function ($template) {
                return [
                    'id' => $template->id,
                    'name' => $template->name,
                    'content' => $template->content,
                    'keywords' => $template->keywords,
                    'category' => $template->category,
                    'success_rate' => $template->success_rate,
                ];
            })->toArray();
        });
    }

    /**
     * Update reply templates
     *
     * @param array $templates
     * @return bool
     */
    public function updateTemplates(array $templates): bool
    {
        try {
            // Clear existing templates
            SmartReplyTemplate::truncate();

            // Insert new templates
            foreach ($templates as $template) {
                SmartReplyTemplate::create([
                    'name' => $template['name'],
                    'content' => $template['content'],
                    'keywords' => $template['keywords'],
                    'category' => $template['category'],
                    'success_rate' => $template['success_rate'] ?? 0,
                ]);
            }

            // Clear cache
            Cache::forget('smart_reply_templates');

            return true;
        } catch (\Exception $e) {
            Log::error('Error updating templates: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get message context for personalization
     *
     * @param Message $message
     * @return array
     */
    private function getMessageContext(Message $message): array
    {
        return [
            'customer_name' => $message->customer->name ?? 'there',
            'message_type' => $message->type,
            'channel' => $message->channel,
            'previous_messages' => $message->conversation->messages()
                ->where('id', '<', $message->id)
                ->latest()
                ->take(5)
                ->get()
                ->pluck('content')
                ->toArray(),
        ];
    }

    /**
     * Find matching template based on content and context
     *
     * @param string $content
     * @param array $context
     * @return SmartReplyTemplate|null
     */
    private function findMatchingTemplate(string $content, array $context): ?SmartReplyTemplate
    {
        $templates = SmartReplyTemplate::all();
        $bestMatch = null;
        $highestScore = 0;

        foreach ($templates as $template) {
            $score = $this->calculateTemplateMatchScore($template, $content, $context);
            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $template;
            }
        }

        return $bestMatch;
    }

    /**
     * Calculate how well a template matches the message
     *
     * @param SmartReplyTemplate $template
     * @param string $content
     * @param array $context
     * @return float
     */
    private function calculateTemplateMatchScore(SmartReplyTemplate $template, string $content, array $context): float
    {
        $score = 0;

        // Check keyword matches
        foreach ($template->keywords as $keyword) {
            if (stripos($content, $keyword) !== false) {
                $score += 1;
            }
        }

        // Consider success rate
        $score += $template->success_rate * 0.5;

        // Consider category match
        if ($template->category === $context['message_type']) {
            $score += 2;
        }

        return $score;
    }

    /**
     * Personalize the reply template with context
     *
     * @param SmartReplyTemplate $template
     * @param array $context
     * @return string
     */
    private function personalizeReply(SmartReplyTemplate $template, array $context): string
    {
        $reply = $template->content;

        // Replace placeholders with context values
        $replacements = [
            '{customer_name}' => $context['customer_name'],
            '{channel}' => $context['channel'],
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $reply);
    }

    protected function getMessageAnalysis(Message $message): array
    {
        // Try to get from database first
        $analysis = MessageAnalysis::where('message_id', $message->id)->first();
        
        if ($analysis) {
            return [
                'sentiment' => $analysis->sentiment,
                'intent' => $analysis->intent,
                'keywords' => $analysis->keywords
            ];
        }
        
        // Fallback to advanced analysis service
        $advancedAnalysisService = app(AdvancedMessageAnalysisService::class);
        return $advancedAnalysisService->analyzeMessage($message);
    }

    protected function createReply(Message $message, array $analysis): string
    {
        $intent = $analysis['intent'];
        $sentiment = $analysis['sentiment'];
        $keywords = $analysis['keywords'] ?? [];
        
        // Get base template
        $template = $this->replyTemplates[$intent][$sentiment] ?? $this->replyTemplates['inquiry']['neutral'];
        
        // Generate custom response based on keywords
        $customResponse = $this->generateCustomResponse($intent, $keywords);
        
        // Replace placeholder
        return str_replace('{custom_response}', $customResponse, $template);
    }

    protected function generateCustomResponse(string $intent, array $keywords): string
    {
        switch ($intent) {
            case 'inquiry':
                return 'سنقوم بالرد على استفسارك في أقرب وقت ممكن.';
            case 'complaint':
                return 'سيتم معالجة شكواك من قبل فريق الدعم الفني.';
            case 'suggestion':
                return 'نقدر اقتراحك وسنأخذه بعين الاعتبار.';
            case 'booking':
                return 'سيتم التواصل معك قريباً لتأكيد موعد الحجز.';
            case 'product_request':
                return 'سيتم إرسال معلومات المنتج المطلوب قريباً.';
            case 'angry':
                return 'نعتذر عن الإزعاج وسنقوم بمعالجة الأمر فوراً.';
            case 'spam':
                return 'نشكرك على تواصلك معنا.';
            default:
                return 'سنقوم بالرد على رسالتك في أقرب وقت ممكن.';
        }
    }

    protected function getDefaultReply(): string
    {
        return 'شكراً لتواصلك معنا. سنقوم بالرد على رسالتك في أقرب وقت ممكن.';
    }
} 